# Backend Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyediakan autentikasi demo lokal berbasis Laravel Fortify dan Inertia yang mencakup registrasi, login, logout, verifikasi email melalui Mailpit, reset password, throttling, dan perlindungan route Todo.

**Architecture:** Fortify menyediakan route dan backend autentikasi headless, sedangkan callback view merender Page Inertia Vue. `User` menerapkan `MustVerifyEmail`; route aplikasi dilindungi middleware `auth` dan `verified`. Workspace personal sengaja tidak dibuat dalam plan ini karena model Workspace baru diperkenalkan pada plan subsistem berikutnya, lalu dipicu dari event `Verified`.

**Tech Stack:** Laravel 12, Laravel Fortify, Inertia Laravel 3, Vue 3, MySQL 8.4, PHPUnit 11, Mailpit.

---

## File Map

**Package dan konfigurasi:**

- Modify: `composer.json` dan `composer.lock` — dependency Fortify.
- Create: `config/fortify.php` — fitur, redirect, guard, dan limiter Fortify.
- Create: `app/Providers/FortifyServiceProvider.php` — action binding, Inertia view callback, dan rate limiter.
- Modify: `bootstrap/providers.php` — registrasi provider aplikasi.

**Backend autentikasi:**

- Modify: `app/Models/User.php` — kontrak verifikasi email.
- Create: `app/Actions/Fortify/CreateNewUser.php` — validasi dan pembuatan user.
- Create: `app/Actions/Fortify/PasswordValidationRules.php` — aturan password demo.
- Create: `app/Actions/Fortify/ResetUserPassword.php` — reset password.
- Create: `app/Actions/Fortify/UpdateUserPassword.php` — action bawaan yang dipublikasikan Fortify, meski profile UI belum masuk scope.
- Create: `app/Actions/Fortify/UpdateUserProfileInformation.php` — action bawaan yang dipublikasikan Fortify, meski profile UI belum masuk scope.
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` — share user terautentikasi secara aman.
- Modify: `routes/web.php` — landing redirect dan route Todo terproteksi.

**Inertia auth shell:**

- Create: `resources/js/layouts/AuthLayout.vue` — layout form autentikasi fungsional.
- Create: `resources/js/components/shared/FieldError.vue` — pesan validasi reusable.
- Create: `resources/js/Pages/Auth/Login.vue`.
- Create: `resources/js/Pages/Auth/Register.vue`.
- Create: `resources/js/Pages/Auth/ForgotPassword.vue`.
- Create: `resources/js/Pages/Auth/ResetPassword.vue`.
- Create: `resources/js/Pages/Auth/VerifyEmail.vue`.

Auth shell hanya memastikan alur backend dapat dipakai. Tidak menggunakan Bootstrap. Penggantian visual dengan komponen shadcn-vue dilakukan pada fase UI setelah backend stabil.

**Tests:**

- Create: `tests/Feature/Auth/RegistrationTest.php`.
- Create: `tests/Feature/Auth/AuthenticationTest.php`.
- Create: `tests/Feature/Auth/EmailVerificationTest.php`.
- Create: `tests/Feature/Auth/PasswordResetTest.php`.
- Create: `tests/Feature/Auth/ProtectedTodoRouteTest.php`.
- Remove: `tests/Feature/ExampleTest.php` setelah cakupannya digantikan test route terproteksi.

**Documentation:**

- Modify: `docs/ai-handoff/BACKEND_PROGRESS.md` — bukti implementasi, command, hasil, commit, dan next action.

---

### Task 1: Install and publish Laravel Fortify

**Files:**

- Modify: `composer.json`
- Modify: `composer.lock`
- Create through installer: `config/fortify.php`
- Create through installer: `app/Providers/FortifyServiceProvider.php`
- Create through installer: `app/Actions/Fortify/*.php`
- Modify through installer: `bootstrap/providers.php`
- May create through installer: `database/migrations/*_add_two_factor_columns_to_users_table.php`

- [ ] **Step 1: Confirm the authentication package is absent**

Run:

```powershell
rg -n '"name": "laravel/fortify"' composer.lock
```

Expected: exit code `1` and no match.

- [ ] **Step 2: Install the compatible Fortify release**

Run:

```powershell
& 'C:\xampp\php\php.exe' 'C:\ProgramData\ComposerSetup\bin\composer.phar' require laravel/fortify
```

Expected: Composer updates `composer.json` and `composer.lock` without dependency conflict.

- [ ] **Step 3: Publish the Fortify application files**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan fortify:install
```

Expected: Fortify config, provider, actions, and optional 2FA migration are generated. The 2FA feature remains disabled in Task 2; generated columns may remain to support the documented future hardening phase.

- [ ] **Step 4: Verify provider registration**

Ensure `bootstrap/providers.php` contains:

```php
<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
];
```

- [ ] **Step 5: Verify Fortify is discoverable**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan about
& 'C:\xampp\php\php.exe' artisan route:list --path=login
```

Expected: Laravel boots successfully and Fortify exposes `GET /login` plus `POST /login`.

- [ ] **Step 6: Commit the package foundation**

```powershell
git add composer.json composer.lock config/fortify.php app/Providers/FortifyServiceProvider.php app/Actions/Fortify bootstrap/providers.php database/migrations
git commit -m "chore: install Laravel Fortify"
```

---

### Task 2: Configure verified-user authentication and rate limiting

**Files:**

- Create: `tests/Feature/Auth/RegistrationTest.php`
- Modify: `app/Models/User.php`
- Modify: `config/fortify.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `app/Actions/Fortify/PasswordValidationRules.php`
- Modify: `app/Actions/Fortify/CreateNewUser.php`
- Modify: `app/Actions/Fortify/ResetUserPassword.php`

- [ ] **Step 1: Write the failing registration and verification-contract tests**

Create `tests/Feature/Auth/RegistrationTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_requires_email_verification(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User());
    }

    public function test_new_user_can_register_and_receives_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Riyadh',
            'email' => 'riyadh@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::query()->where('email', 'riyadh@example.test')->firstOrFail();

        $response->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_short_password(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Riyadh',
            'email' => 'riyadh@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'riyadh@example.test']);
    }
}
```

- [ ] **Step 2: Run the test to verify the missing contract or route behavior**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/RegistrationTest.php
```

Expected: FAIL because `User` does not yet implement `MustVerifyEmail`, or because final Fortify configuration is not complete.

- [ ] **Step 3: Make `User` require email verification**

Update the class declaration and import in `app/Models/User.php`:

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
```

Keep the existing `HasFactory`, `Notifiable`, fillable, hidden, and casts definitions.

- [ ] **Step 4: Lock Fortify to the demo feature set**

In `config/fortify.php`, set:

```php
'home' => '/app',

'views' => true,

'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
],

'limiters' => [
    'login' => 'login',
    'two-factor' => 'two-factor',
],
```

Do not enable profile updates, password profile screens, or two-factor authentication in the demo phase.

- [ ] **Step 5: Use one password rule for registration and reset**

Set `app/Actions/Fortify/PasswordValidationRules.php` to:

```php
<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::min(8), 'confirmed'];
    }
}
```

Set `app/Actions/Fortify/CreateNewUser.php` to:

```php
<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $email = Str::lower($input['email']);

        Validator::make([
            ...$input,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $email,
            'password' => Hash::make($input['password']),
        ]);
    }
}
```

Set `app/Actions/Fortify/ResetUserPassword.php` to:

```php
<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
```

- [ ] **Step 6: Configure Fortify action bindings and login limiter**

Replace `app/Providers/FortifyServiceProvider.php` with:

```php
<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input(Fortify::username()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
```

- [ ] **Step 7: Run registration tests**

Run:

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/RegistrationTest.php
```

Expected: all registration tests PASS.

- [ ] **Step 8: Commit verified-user rules**

```powershell
git add app/Models/User.php config/fortify.php app/Providers/FortifyServiceProvider.php app/Actions/Fortify tests/Feature/Auth/RegistrationTest.php
git commit -m "feat: configure verified user registration"
```

---

### Task 3: Add functional Inertia authentication pages

**Files:**

- Create: `resources/js/layouts/AuthLayout.vue`
- Create: `resources/js/components/shared/FieldError.vue`
- Create: `resources/js/Pages/Auth/Login.vue`
- Create: `resources/js/Pages/Auth/Register.vue`
- Create: `resources/js/Pages/Auth/ForgotPassword.vue`
- Create: `resources/js/Pages/Auth/ResetPassword.vue`
- Create: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Create: `tests/Feature/Auth/AuthPagesTest.php`

- [ ] **Step 1: Write failing Inertia page tests**

Create `tests/Feature/Auth/AuthPagesTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_auth_pages_render_with_inertia(): void
    {
        $this->get('/login')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Auth/Login')
        );

        $this->get('/register')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Auth/Register')
        );

        $this->get('/forgot-password')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Auth/ForgotPassword')
        );
    }

    public function test_unverified_user_can_render_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/VerifyEmail'));
    }
}
```

- [ ] **Step 2: Run the page tests to verify they fail**

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/AuthPagesTest.php
```

Expected: FAIL because Fortify view callbacks are not registered.

- [ ] **Step 3: Register Inertia view callbacks**

Add these imports to `FortifyServiceProvider`:

```php
use Inertia\Inertia;
```

Add these callbacks inside `boot()` before the limiter:

```php
Fortify::loginView(fn () => Inertia::render('Auth/Login', [
    'status' => session('status'),
]));

Fortify::registerView(fn () => Inertia::render('Auth/Register'));

Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword', [
    'status' => session('status'),
]));

Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', [
    'email' => $request->email,
    'token' => $request->route('token'),
]));

Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail', [
    'status' => session('status'),
]));
```

- [ ] **Step 4: Create the shared auth layout and field error**

Create `resources/js/layouts/AuthLayout.vue`:

```vue
<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-blue-700">To Do List KAI</p>
            <h1 class="mt-2 text-2xl font-bold">{{ title }}</h1>
            <p v-if="description" class="mt-2 text-sm text-slate-600">{{ description }}</p>
            <div class="mt-6"><slot /></div>
        </section>
    </main>
</template>
```

Create `resources/js/components/shared/FieldError.vue`:

```vue
<script setup>
defineProps({ message: { type: String, default: '' } });
</script>

<template>
    <p v-if="message" class="mt-1 text-sm text-red-600">{{ message }}</p>
</template>
```

- [ ] **Step 5: Create login and registration pages**

Create `resources/js/Pages/Auth/Login.vue`:

```vue
<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout title="Masuk" description="Gunakan akun yang sudah terdaftar.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Email
                <input v-model="form.email" type="email" autocomplete="username" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.email" />
            </label>
            <label class="block text-sm font-medium">Password
                <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.password" />
            </label>
            <label class="flex items-center gap-2 text-sm"><input v-model="form.remember" type="checkbox" /> Ingat saya</label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Masuk</button>
        </form>
        <div class="mt-4 flex justify-between text-sm">
            <Link href="/forgot-password" class="text-blue-700">Lupa password?</Link>
            <Link href="/register" class="text-blue-700">Daftar</Link>
        </div>
    </AuthLayout>
</template>
```

Create `resources/js/Pages/Auth/Register.vue` with the same field classes and this script/form payload:

```vue
<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Daftar" />
    <AuthLayout title="Buat akun" description="Verifikasi email diperlukan sebelum memakai aplikasi.">
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Nama<input v-model="form.name" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.name" /></label>
            <label class="block text-sm font-medium">Email<input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.email" /></label>
            <label class="block text-sm font-medium">Password<input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.password" /></label>
            <label class="block text-sm font-medium">Konfirmasi password<input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-lg border px-3 py-2" /></label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Daftar</button>
        </form>
        <Link href="/login" class="mt-4 block text-center text-sm text-blue-700">Sudah punya akun?</Link>
    </AuthLayout>
</template>
```

- [ ] **Step 6: Create forgot-password, reset-password, and verification pages**

Create `resources/js/Pages/Auth/ForgotPassword.vue`:

```vue
<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <Head title="Lupa password" />
    <AuthLayout title="Lupa password" description="Kami akan mengirim tautan reset ke Mailpit.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Email
                <input v-model="form.email" type="email" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.email" />
            </label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Kirim tautan reset</button>
        </form>
        <Link href="/login" class="mt-4 block text-center text-sm text-blue-700">Kembali ke login</Link>
    </AuthLayout>
</template>
```

Create `resources/js/Pages/Auth/ResetPassword.vue`:

```vue
<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Reset password" />
    <AuthLayout title="Reset password" description="Gunakan password baru minimal delapan karakter.">
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Email
                <input v-model="form.email" type="email" required autocomplete="username" class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.email" />
            </label>
            <label class="block text-sm font-medium">Password baru
                <input v-model="form.password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.password" />
            </label>
            <label class="block text-sm font-medium">Konfirmasi password
                <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border px-3 py-2" />
            </label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Simpan password</button>
        </form>
    </AuthLayout>
</template>
```

Create `resources/js/Pages/Auth/VerifyEmail.vue`:

```vue
<script setup>
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });
const resend = useForm({});
</script>

<template>
    <Head title="Verifikasi email" />
    <AuthLayout title="Verifikasi email" description="Buka Mailpit, lalu klik tautan verifikasi yang kami kirim.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="resend.post('/email/verification-notification')">Kirim ulang email</button>
        <Link href="/logout" method="post" as="button" class="mt-4 w-full text-sm text-slate-600">Keluar</Link>
    </AuthLayout>
</template>
```

The forgot/reset pages must display every Fortify field error through `FieldError` and disable submit while processing. Do not add visual dependencies or Bootstrap.

- [ ] **Step 7: Run page tests and frontend build**

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/AuthPagesTest.php
npm.cmd run build
```

Expected: auth page tests PASS and Vite build exits `0`.

- [ ] **Step 8: Commit the Inertia auth shell**

```powershell
git add app/Providers/FortifyServiceProvider.php resources/js/layouts/AuthLayout.vue resources/js/components/shared/FieldError.vue resources/js/Pages/Auth tests/Feature/Auth/AuthPagesTest.php
git commit -m "feat: add Inertia authentication pages"
```

---

### Task 4: Implement and verify login, logout, and protected Todo routing

**Files:**

- Create: `tests/Feature/Auth/AuthenticationTest.php`
- Create: `tests/Feature/Auth/ProtectedTodoRouteTest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Remove: `tests/Feature/ExampleTest.php`

- [ ] **Step 1: Write failing authentication tests**

Create `tests/Feature/Auth/AuthenticationTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/app');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_failed_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $key = Str::lower($user->email).'|127.0.0.1';

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
    }
}
```

Create `tests/Feature/Auth/ProtectedTodoRouteTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProtectedTodoRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/app')->assertRedirect('/email/verify');
    }

    public function test_verified_user_can_open_todo_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Todo/Index')
                ->where('auth.user.id', $user->id)
                ->where('auth.user.email', $user->email));
    }
}
```

- [ ] **Step 2: Run tests to verify route protection is missing**

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/ProtectedTodoRouteTest.php
```

Expected: authentication tests may pass through Fortify, while protected route tests FAIL because `/app` and shared auth props do not exist.

- [ ] **Step 3: Protect the Todo route**

Replace `routes/web.php` with:

```php
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'todo.index' : 'login'));

Route::get('/app', fn () => Inertia::render('Todo/Index'))
    ->middleware(['auth', 'verified'])
    ->name('todo.index');
```

- [ ] **Step 4: Share the minimal authenticated user**

Update the `share()` return value in `HandleInertiaRequests`:

```php
return [
    ...parent::share($request),
    'auth' => [
        'user' => fn () => $request->user()?->only([
            'id',
            'name',
            'email',
            'email_verified_at',
        ]),
    ],
];
```

Do not share password, remember token, session payload, or future security fields.

- [ ] **Step 5: Remove the obsolete public-root test and rerun focused tests**

Delete `tests/Feature/ExampleTest.php`, then run:

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/ProtectedTodoRouteTest.php
```

Expected: all tests PASS.

- [ ] **Step 6: Commit protected routing**

```powershell
git add routes/web.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/ProtectedTodoRouteTest.php tests/Feature/ExampleTest.php
git commit -m "feat: protect Todo routes with verified auth"
```

---

### Task 5: Verify email verification flow and Mailpit integration

**Files:**

- Create: `tests/Feature/Auth/EmailVerificationTest.php`
- Verify: `app/Models/User.php`
- Modify: `.env.example`
- Modify locally without committing: `.env`

- [ ] **Step 1: Write email verification tests**

Create `tests/Feature/Auth/EmailVerificationTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_be_verified_with_signed_link(): void
    {
        Event::fake();
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/app');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_invalid_signature_does_not_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get("/email/verify/{$user->id}/".sha1($user->email).'?signature=invalid')
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent_with_throttled_route(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertSessionHas('status');

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
```

- [ ] **Step 2: Run verification tests**

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/EmailVerificationTest.php
```

Expected: all tests PASS using Fortify's signed verification routes.

- [ ] **Step 3: Align local URL and Mailpit configuration**

Set these tracked values in `.env.example`:

```dotenv
APP_NAME="To Do List KAI"
APP_URL=http://127.0.0.1:8000

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS="todo@kai.local"
MAIL_FROM_NAME="${APP_NAME}"
```

Mirror the same non-secret URL and mail settings into the ignored local `.env`
without copying any password or key into documentation or Git. Then run:

```powershell
& 'C:\xampp\php\php.exe' artisan config:clear
```

Expected: configuration cache is cleared so signed verification links use
`http://127.0.0.1:8000`.

- [ ] **Step 4: Verify Mailpit without exposing `.env`**

Run:

```powershell
Select-String -Path .env.example -Pattern 'MAIL_HOST=127.0.0.1','MAIL_PORT=1025'
(Invoke-WebRequest -UseBasicParsing -Uri 'http://127.0.0.1:8025' -TimeoutSec 10).StatusCode
```

Expected: `.env.example` points to Mailpit and the UI returns HTTP `200`.

- [ ] **Step 5: Commit verification coverage and safe environment defaults**

```powershell
git add tests/Feature/Auth/EmailVerificationTest.php .env.example
git commit -m "test: cover email verification flow"
```

---

### Task 6: Verify password reset

**Files:**

- Create: `tests/Feature/Auth/PasswordResetTest.php`
- Verify: `app/Actions/Fortify/ResetUserPassword.php`

- [ ] **Step 1: Write password reset tests**

Create `tests/Feature/Auth/PasswordResetTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

            $response->assertSessionHasNoErrors()->assertRedirect('/login');

            return true;
        });

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

}
```

- [ ] **Step 2: Run password reset tests**

```powershell
& 'C:\xampp\php\php.exe' artisan test tests/Feature/Auth/PasswordResetTest.php
```

Expected: both tests PASS.

- [ ] **Step 3: Commit password reset coverage**

```powershell
git add tests/Feature/Auth/PasswordResetTest.php app/Actions/Fortify/ResetUserPassword.php app/Providers/FortifyServiceProvider.php
git commit -m "test: secure password reset flow"
```

---

### Task 7: Run the full authentication checkpoint

**Files:**

- Verify: all files from Tasks 1-6
- Modify: `docs/ai-handoff/BACKEND_PROGRESS.md`

- [ ] **Step 1: Clear cached application state**

```powershell
& 'C:\xampp\php\php.exe' artisan optimize:clear
```

Expected: configuration, route, event, and view caches are cleared.

- [ ] **Step 2: Run database migrations against local MySQL**

```powershell
& 'C:\xampp\php\php.exe' artisan migrate
& 'C:\xampp\php\php.exe' artisan migrate:status
```

Expected: all migrations, including any Fortify-published migration, report `Ran` against `todo_list_kai` on port 3307.

- [ ] **Step 3: Run all PHP tests**

```powershell
& 'C:\xampp\php\php.exe' artisan test
```

Expected: every test PASS with no failure or error.

- [ ] **Step 4: Run frontend build**

```powershell
npm.cmd run build
```

Expected: Vite build exits `0` and resolves every Auth Page.

- [ ] **Step 5: Inspect auth routes**

```powershell
& 'C:\xampp\php\php.exe' artisan route:list --except-vendor
```

Expected: application routes include the protected `/app`; Fortify routes are visible when running the complete route list.

- [ ] **Step 6: Perform a local Mailpit smoke test**

Start Laravel and the queue worker in separate terminals:

```powershell
& 'C:\xampp\php\php.exe' artisan serve
& 'C:\xampp\php\php.exe' artisan queue:work --tries=3
```

Register one disposable local user, open `http://127.0.0.1:8025`, click the verification link, and verify `/app` becomes accessible. Do not commit the local user or any `.env` value.

- [ ] **Step 7: Update the backend handoff**

In `docs/ai-handoff/BACKEND_PROGRESS.md`:

- Mark the spec as approved.
- Mark Phase 0 complete.
- Mark Phase 1 complete only if all automated tests and Mailpit smoke test pass.
- Record the Fortify version from `composer.lock`.
- Record migration names, route evidence, test count, build result, and commit hashes.
- Record Docker API permission limitations separately from verified MySQL and HTTP endpoint health.
- Set the next action to writing the Workspace and Membership implementation plan.

- [ ] **Step 8: Check and commit documentation**

```powershell
git diff --check
git status -sb
git add docs/ai-handoff/BACKEND_PROGRESS.md
git commit -m "docs: record authentication backend progress"
```

Expected: handoff matches the actual verified state and working tree is clean.

---

## Deferred from This Plan

- Personal workspace provisioning; implement with the Workspace domain and a
  listener for Laravel's `Verified` event in the next plan.
- Activity/security event persistence; connect Fortify events after the
  immutable Activity Log model exists.
- TOTP 2FA, recovery codes, 12-character production password rules, leaked
  password checks, generic reset-link responses, HTTPS, and production SMTP.
- Final shadcn-vue visual design. The functional auth shell must not introduce
  Bootstrap or another component library.

## Completion Gate

Do not start Workspace implementation until:

- Auth tests pass.
- MySQL migrations report `Ran`.
- Vite build passes.
- Registration, Mailpit verification, login, logout, and reset-password smoke
  flows are verified.
- `BACKEND_PROGRESS.md` is updated with evidence and the next plan is named.
