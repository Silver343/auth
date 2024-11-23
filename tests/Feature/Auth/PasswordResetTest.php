<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('user is redirected to two factor challenge screen if it is enabled and confirmed', function () {
    Notification::fake();

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset.two-factor.challenge', ['token' => $notification->token, 'email' => $user->email]));

        return true;
    });
});

test('user is redirected to password reset screen after confirming 2fa with code', function () {
    Notification::fake();

    $tfaEngine = app(Google2FA::class);
    $userSecret = $tfaEngine->generateSecretKey();
    $validOTP = $tfaEngine->getCurrentOtp($userSecret);

    $user = User::factory()->state([
        'two_factor_secret' => encrypt($userSecret),
    ])->confirmed()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $validOTP) {
        post(route('password.reset.two-factor.confirm', [
            'token' => $notification->token,
            'email' => $user->email,
            'code' => $validOTP,
        ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('auth.2fa_confirmed_at')
            ->assertRedirect(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        return true;
    });
});

test('user is redirected to password reset screen after confirming 2fa with recovery code', function () {
    Notification::fake();

    $user = User::factory()
        ->withTwoFactor()
        ->confirmed()
        ->state([
            'two_factor_recovery_codes' => encrypt(json_encode(['valid-code'])),
        ])
        ->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        post(route('password.reset.two-factor.confirm', [
            'token' => $notification->token,
            'email' => $user->email,
            'recovery_code' => 'valid-code',
        ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('auth.2fa_confirmed_at')
            ->assertRedirect(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        return true;
    });
});

test('user is not redirect to confirm 2fa if recently confirmed', function () {
    Notification::fake();

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        withSession(['auth.2fa_confirmed_at' => time()])
            ->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->assertStatus(200)
            ->assertSessionHas('auth.2fa_confirmed_at');

        return true;
    });
});

test('user is redirect to confirm 2fa if confirmed in the past beyond the auth.two_factor.timeout', function () {
    Notification::fake();

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        withSession(['auth.2fa_confirmed_at' => now()->subDay()->unix()])
            ->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->assertRedirect(route('password.reset.two-factor.challenge', ['token' => $notification->token, 'email' => $user->email]));

        return true;
    });
});
