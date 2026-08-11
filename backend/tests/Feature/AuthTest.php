<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'display_name' => 'Test Reader',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.is_verified', false);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'reader',
            'is_active' => true,
        ]);

        Notification::assertSentTo(
            User::where('email', 'test@example.com')->first(),
            VerifyEmailNotification::class,
        );
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'display_name' => 'Duplicate',
            'email' => 'dup@example.com',
            'password' => 'secret123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_weak_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'display_name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => 'abc',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com', 'password' => 'secret123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => ['access_token', 'expires_at', 'user'],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create(['email' => 'nope@example.com', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'nope@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_for_deactivated_user_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'gone@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'gone@example.com',
            'password' => 'secret123',
        ])->assertStatus(403);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Authentication required.');
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $token = $user->createAccessToken()['token'];

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'me@example.com');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create(['email' => 'out@example.com']);
        $token = $user->createAccessToken()['token'];

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertStatus(204);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_email_can_be_verified_via_signed_url(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'verify@example.com']);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.message', 'Your email address has been verified.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_rejects_tampered_signature(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'tamper@example.com']);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]).'&signature=0';

        $this->getJson($url)->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/password/reset', ['email' => 'reset@example.com'])
            ->assertOk()
            ->assertJsonPath('data.message', 'If that email address exists, a password reset link has been sent.');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_does_not_leak_account_existence(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/password/reset', ['email' => 'ghost@example.com'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_and_tokens_revoked(): void
    {
        $user = User::factory()->create(['email' => 'reset2@example.com', 'password' => 'oldpass123']);
        $token = $user->createAccessToken()['token'];

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $status = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/password/reset/confirm', [
            'token' => $status,
            'email' => $user->email,
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ])->assertOk()
            ->assertJsonPath('data.message', 'Your password has been reset. Please sign in.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'newpass456',
        ])->assertOk();
    }

    public function test_password_reset_with_invalid_token_fails(): void
    {
        $user = User::factory()->create(['email' => 'reset3@example.com']);

        $this->postJson('/api/v1/auth/password/reset/confirm', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ])->assertStatus(422);
    }

    public function test_access_token_expiration_is_configured(): void
    {
        $user = User::factory()->create();

        $result = $user->createAccessToken();

        $this->assertNotNull($result['expires_at']);
    }
}
