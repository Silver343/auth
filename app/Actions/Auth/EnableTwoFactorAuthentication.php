<?php

namespace App\Actions\Auth;

use App\Events\Auth\TwoFactorAuthenticationEnabled;
use App\Providers\TwoFactorAuthenticationProvider;
use App\RecoveryCode;
use Illuminate\Support\Collection;

class EnableTwoFactorAuthentication
{
    /**
     * The two factor authentication provider.
     *
     * @var App\providers\TwoFactorAuthenticationProvider
     */
    protected $provider;

    /**
     * Create a new action instance.
     *
     * @param  App\Providers\TwoFactorAuthenticationProvider  $provider
     * @return void
     */
    public function __construct(TwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Enable two factor authentication for the user.
     *
     * @param  mixed  $user
     * @param  bool  $force
     * @return void
     */
    public function __invoke($user, $force = false)
    {
        if (empty($user->two_factor_secret) || $force === true) {
            $secretLength = (int) config('auth.two-factor.secret-length', 16);

            $user->forceFill([
                'two_factor_secret' => encrypt($this->provider->generateSecretKey($secretLength)),
                'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                    return RecoveryCode::generate();
                })->all())),
            ])->save();

            TwoFactorAuthenticationEnabled::dispatch($user);
        }
    }
}
