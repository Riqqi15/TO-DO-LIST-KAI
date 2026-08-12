<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_without_losing_verification(): void
    {
        $user = User::factory()->create(['name' => 'Nama Lama', 'email' => 'lama@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => 'Nama Baru', 'email' => 'lama@example.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_changing_email_clears_verification_timestamp(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'email' => 'baru@example.com'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_must_be_lowercase_and_unique(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);
        User::factory()->create(['email' => 'dipakai@example.com']);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'email' => 'Dipakai@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertSame('lama@example.com', $user->fresh()->email);
    }

    public function test_password_update_requires_matching_current_password(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'salah',
            'password' => 'kata-sandi-baru-1',
            'password_confirmation' => 'kata-sandi-baru-1',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'kata-sandi-lama',
            'password' => 'kata-sandi-baru-1',
            'password_confirmation' => 'kata-sandi-baru-1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('kata-sandi-baru-1', $user->fresh()->password));
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->put(route('profile.update'), ['name' => 'Nama', 'email' => 'nama@example.com'])
            ->assertRedirect(route('login'));
    }
}
