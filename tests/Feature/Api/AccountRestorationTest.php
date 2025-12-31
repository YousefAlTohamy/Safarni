<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\OtpType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountRestorationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_registration_restores_soft_deleted_account(): void
    {
        // Create a soft-deleted user
        $user = User::factory()->create([
            'email' => 'restore@gmail.com',
            'password' => Hash::make('OldPassword1!'),
            'name' => 'Old Name',
            'deleted_at' => now(),
        ]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Register with same email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Name',
            'email' => 'restore@gmail.com',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Account restored successfully. Please check your email for the verification code.',
            ]);

        // Check user restored
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertTrue(Hash::check('NewPassword1!', $user->password));
        $this->assertEquals('active', $user->status); // Should be active per logic
        $this->assertFalse($user->is_verified);
        $this->assertNull($user->email_verified_at);

        // Verify OTP sent
        $this->assertDatabaseHas('otps', [
            'email' => 'restore@gmail.com',
            'type' => OtpType::VERIFICATION->value,
        ]);
    }

    public function test_registration_validates_active_email_unique(): void
    {
        // Active user
        User::factory()->create([
            'email' => 'active@gmail.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Name',
            'email' => 'active@gmail.com',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
