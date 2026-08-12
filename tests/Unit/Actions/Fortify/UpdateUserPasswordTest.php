<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_replaced_when_current_password_matches(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $this->actingAs($user);

        app(UpdateUserPassword::class)->update($user, [
            'current_password' => 'kata-sandi-lama',
            'password' => 'kata-sandi-baru-1',
            'password_confirmation' => 'kata-sandi-baru-1',
        ]);

        $this->assertTrue(Hash::check('kata-sandi-baru-1', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $this->actingAs($user);

        $errors = $this->assertUpdateFails($user, [
            'current_password' => 'salah',
            'password' => 'kata-sandi-baru-1',
            'password_confirmation' => 'kata-sandi-baru-1',
        ]);

        $this->assertArrayHasKey('current_password', $errors);
        $this->assertTrue(Hash::check('kata-sandi-lama', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed_and_long_enough(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);
        $this->actingAs($user);

        $this->assertArrayHasKey('password', $this->assertUpdateFails($user, [
            'current_password' => 'kata-sandi-lama',
            'password' => 'kata-sandi-baru-1',
            'password_confirmation' => 'beda',
        ]));

        $this->assertArrayHasKey('password', $this->assertUpdateFails($user, [
            'current_password' => 'kata-sandi-lama',
            'password' => 'ab1',
            'password_confirmation' => 'ab1',
        ]));

        $this->assertTrue(Hash::check('kata-sandi-lama', $user->fresh()->password));
    }

    /**
     * @param  array<string, string>  $input
     * @return array<string, array<int, string>>
     */
    private function assertUpdateFails(User $user, array $input): array
    {
        try {
            app(UpdateUserPassword::class)->update($user, $input);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            return $exception->errors();
        }
    }
}
