<?php

use App\Events\Auth\TwoFactorAuthenticationChallenged;
use App\Events\Auth\TwoFactorAuthenticationFailed;
use App\Events\Auth\ValidTwoFactorAuthenticationCodeProvided;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withoutExceptionHandling;
use function Pest\Laravel\withSession;

test('user is redirected to challenge when using two factor authentication', function () {
    Event::fake();

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    withoutExceptionHandling();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/two-factor-challenge');

    Event::assertDispatched(TwoFactorAuthenticationChallenged::class);
});

test('user is not redirected to challenge when using two factor authentication that has not been confirmed', function () {

    Event::fake();

    $user = User::factory()->withTwoFactor()->create();

    withoutExceptionHandling();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));
});

test('user password is rehashed when redirecting to two factor challenge', function () {

    $user = User::factory()->state([
        'password' => Hash::make('password', ['rounds' => 4]),
    ])->withTwoFactor()->confirmed()->create();

    withoutExceptionHandling();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/two-factor-challenge');

    $this->assertNotSame($user->password, $user->fresh()->password);
    $this->assertTrue(Hash::check('password', $user->fresh()->password));
});

test('two factor challenge can be passed via code', function () {
    Event::fake();

    $tfaEngine = app(Google2FA::class);
    $userSecret = $tfaEngine->generateSecretKey();
    $validOTP = $tfaEngine->getCurrentOtp($userSecret);

    $user = user::factory()->state([
        'two_factor_secret' => encrypt($userSecret),
    ])->confirmed()->create();

    withSession([
        'login.id' => $user->id,
        'login.remember' => false,
    ]);

    withoutExceptionHandling();

    post('/two-factor-challenge', [
        'code' => $validOTP,
    ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionMissing('login.id');

    Event::assertDispatched(ValidTwoFactorAuthenticationCodeProvided::class);
});

test('two factor authenticatino preserves remember me selecetion', function () {
    Event::fake();

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    withoutExceptionHandling();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remeber' => false,
    ])
        ->assertRedirect('/two-factor-challenge')
        ->assertSessionHas('login.remember', false);
});

test('two factor challenge fails for old otp', function () {
    Event::fake();

    $tfaEngine = app(Google2FA::class);
    $userSecret = $tfaEngine->generateSecretKey();
    $currentTs = $tfaEngine->getTimestamp();
    $previousOtp = $tfaEngine->oathTotp($userSecret, $currentTs - 2);

    $user = User::factory()
        ->withTwoFactor()
        ->confirmed()
        ->state([
            'two_factor_secret' => encrypt($userSecret),
        ])
        ->create();

    withSession([
        'login.id' => $user->id,
        'login.remember' => false,
    ]);

    withoutExceptionHandling();

    post('/two-factor-challenge', [
        'code' => $previousOtp,
    ])
        ->assertRedirect('/two-factor-challenge')
        ->assertSessionHas('login.id')
        ->assertSessionHasErrors(['code']);

    Event::assertDispatched(TwoFactorAuthenticationFailed::class);
});

test('two factor challenge can be passed via recovery code', function () {

    Event::fake();

    $user = User::factory()
        ->withTwoFactor()
        ->confirmed()
        ->state([
            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
        ])
        ->create();

    withSession([
        'login.id' => $user->id,
        'login.remember' => false,
    ]);

    withoutExceptionHandling();

    post('/two-factor-challenge', [
        'recovery_code' => 'valid-code',
    ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionMissing('login.id');

    Event::assertDispatched(ValidTwoFactorAuthenticationCodeProvided::class);

    $this->assertNotNull(Auth::getUser());
    $this->assertNotContains('valid-code', json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true));
});

test('two factor challenge can fail via recovery code', function () {

    $user = User::factory()
        ->withTwoFactor()
        ->confirmed()
        ->state([
            'two_factor_recovery_codes' => encrypt(json_encode(['invalid-code', 'valid-code'])),
        ])
        ->create();

    withSession([
        'login.id' => $user->id,
        'login.remember' => false,
    ]);

    withoutExceptionHandling();

    post('/two-factor-challenge', [
        'recovery_code' => 'missing-code',
    ])
        ->assertRedirect('/two-factor-challenge')
        ->assertSessionHas('login.id')
        ->assertSessionHasErrors(['recovery_code']);

    $this->assertNull(Auth::getUser());
});

test('two factor challenge requires a challenged user', function () {

    withSession([]);
    withoutExceptionHandling();

    get('/two-factor-challenge')
        ->assertRedirect('/login');

    $this->assertNull(Auth::getUser());
});
