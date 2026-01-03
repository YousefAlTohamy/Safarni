<?php

namespace Tests\Feature\Api;

use App\Enums\OtpType;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_user_cannot_deactivate_without_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_verified' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/deactivate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_deactivate_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_verified' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/deactivate', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Incorrect password.',
            ]);

        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_user_can_deactivate_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/deactivate', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account deactivated successfully.',
            ]);

        $user->refresh();
        $this->assertEquals('inactive', $user->status);
        $this->assertFalse($user->is_verified);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_inactive_user_can_request_reactivation_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@gmail.com',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/auth/resend-otp', [
            'email' => 'inactive@gmail.com',
            'type' => 'reactivation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully.',
            ]);

        $this->assertDatabaseHas('otps', [
            'email' => 'inactive@gmail.com',
            'type' => OtpType::REACTIVATION->value,
        ]);
    }

    public function test_user_can_reactivate_account_with_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@gmail.com',
            'status' => 'inactive',
        ]);

        Otp::create([
            'email' => 'inactive@gmail.com',
            'code' => '1234',
            'type' => OtpType::REACTIVATION->value,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'inactive@gmail.com',
            'code' => '1234',
            'type' => 'reactivation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account reactivated successfully. You can now login.',
            ]);

        $this->assertEquals('active', $user->fresh()->status);
    }

    public function test_reactivation_fails_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@gmail.com',
            'status' => 'inactive',
        ]);

        Otp::create([
            'email' => 'inactive@gmail.com',
            'code' => '1234',
            'type' => OtpType::REACTIVATION->value,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'inactive@gmail.com',
            'code' => '9999',
            'type' => 'reactivation',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertEquals('inactive', $user->fresh()->status);
    }


    public function test_active_user_cannot_use_reactivation_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'active@gmail.com',
            'status' => 'active',
        ]);

        Otp::create([
            'email' => 'active@gmail.com',
            'code' => '1234',
            'type' => OtpType::REACTIVATION->value,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'active@gmail.com',
            'code' => '1234',
            'type' => 'reactivation',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Account is already active.',
            ]);
    }

    public function test_user_cannot_delete_account_without_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/profile');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_delete_account_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Incorrect password.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/profile', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
