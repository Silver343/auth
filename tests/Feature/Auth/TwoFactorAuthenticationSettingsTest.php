<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

test('two factor authentication can be enabled', function () {
    actingAs($user = User::factory()->create());
    withSession(['auth.password_confirmed_at' => time()]);

    post('/user/two-factor-authentication');

    assertNotNull($user->fresh()->two_factor_secret);
    assertCount(8, $user->fresh()->recoveryCodes());
});

test('recovery codes can be regenerated', function () {
    actingAs($user = User::factory()->create());
    withSession(['auth.password_confirmed_at' => time()]);

    post('/user/two-factor-authentication');
    post('/user/two-factor-recovery-codes');

    $user = $user->fresh();

    post('/user/two-factor-recovery-codes');

    assertCount(8, $user->recoveryCodes());
    assertCount(8, array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()));
});

test('two factor authentication can be disabled', function () {

    $user = User::factory()->withTwoFactor()->confirmed()->create();

    actingAs($user);
    withSession(['auth.password_confirmed_at' => time()]);

    post('/user/two-factor-authentication');

    assertNotNull($user->fresh()->two_factor_secret);

    delete('/user/two-factor-authentication');

    assertNull($user->fresh()->two_factor_secret);
});
