<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_only_update_keeps_email_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['name' => 'Nama Lama', 'email' => 'lama@example.com']);

        app(UpdateUserProfileInformation::class)->update($user, [
            'name' => 'Nama Baru',
            'email' => 'lama@example.com',
        ]);

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_changing_email_resets_verification_and_sends_new_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lama@example.com']);

        app(UpdateUserProfileInformation::class)->update($user, [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);

        $user->refresh();
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_must_be_unique_and_name_is_required(): void
    {
        $user = User::factory()->create(['email' => 'lama@example.com']);
        User::factory()->create(['email' => 'dipakai@example.com']);

        $this->assertArrayHasKey('email', $this->assertUpdateFails($user, [
            'name' => 'Nama Baru',
            'email' => 'dipakai@example.com',
        ]));

        $this->assertArrayHasKey('name', $this->assertUpdateFails($user, [
            'name' => '',
            'email' => 'lain@example.com',
        ]));

        $this->assertSame('lama@example.com', $user->fresh()->email);
    }

    /**
     * @param  array<string, string>  $input
     * @return array<string, array<int, string>>
     */
    private function assertUpdateFails(User $user, array $input): array
    {
        try {
            app(UpdateUserProfileInformation::class)->update($user, $input);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            return $exception->errors();
        }
    }
}
