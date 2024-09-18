<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\RecoveryCodeReplaced;
use App\Events\Auth\TwoFactorAuthenticationFailed;
use App\Events\Auth\ValidTwoFactorAuthenticationCodeProvided;
use App\Http\Requests\Auth\TwoFactorLoginRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /**
     * Show the two factor authentication challenge view.
     *
     * @param  App\Http\Requests\Auth\TwoFactorLoginRequest  $request
     */
    public function create(TwoFactorLoginRequest $request): Response
    {
        if (! $request->hasChallengedUser()) {
            throw new HttpResponseException(redirect()->route('login'));
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Attempt to authenticate a new session using the two factor authentication code.
     *
     * @param  App\Http\Requests\Auth\TwoFactorLoginRequest  $request
     * @return mixed
     */
    public function store(TwoFactorLoginRequest $request)
    {
        $user = $request->challengedUser();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);

            event(new RecoveryCodeReplaced($user, $code));
        } elseif (! $request->hasValidCode()) {
            event(new TwoFactorAuthenticationFailed($user));

            return $this->failedTwoFactorLoginResponse($request);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        Auth::guard('web')->login($user, $request->remember());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function failedTwoFactorLoginResponse($request)
    {
        [$key, $message] = $request->filled('recovery_code')
            ? ['recovery_code', __('The provided two factor recovery code was invalid.')]
            : ['code', __('The provided two factor authentication code was invalid.')];

        return redirect()->route('two-factor.login')->withErrors([$key => $message]);
    }
}
