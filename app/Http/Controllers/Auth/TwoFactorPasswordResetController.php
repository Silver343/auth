<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\RecoveryCodeReplaced;
use App\Events\Auth\TwoFactorAuthenticationFailed;
use App\Events\Auth\ValidTwoFactorAuthenticationCodeProvided;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorPasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorPasswordResetController extends Controller
{
    /**
     * Show the two factor authentication challenge view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = User::where('email', $request->email)->sole();

        return Inertia::render('Auth/TwoFactorResetPasswordChallenge', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Attempt to authenticate the two factor authentication code.
     *
     * @param  App\Http\Requests\Auth\TwoFactorPasswordResetRequest  $request
     * @return mixed
     */
    public function store(TwoFactorPasswordResetRequest $request)
    {
        $user = User::where('email', $request->email)->sole();

        if ($code = $request->validRecoveryCode()) {
            $user->replaceRecoveryCode($code);

            event(new RecoveryCodeReplaced($user, $code));
        } elseif (! $request->hasValidCode()) {
            event(new TwoFactorAuthenticationFailed($user));

            return $this->failedTwoFactorResponse($request);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        $request->session()->put('auth.2fa_confirmed_at', time());

        return redirect()->route('password.reset', [
            'email' => $request->validated('email'),
            'token' => $request->validated('token'),
        ]);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function failedTwoFactorResponse($request)
    {
        [$key, $message] = $request->filled('recovery_code')
            ? ['recovery_code', __('The provided two factor recovery code was invalid.')]
            : ['code', __('The provided two factor authentication code was invalid.')];

        return redirect()->route('password.reset.two-factor.challenge', [
            'email' => $request->email,
            'token' => $request->token,
        ])->withErrors([$key => $message]);
    }
}
