<?php

namespace App\Actions\Auth;

use App\Events\Auth\TwoFactorAuthenticationChallenged;
use App\LoginRateLimiter;
use App\Models\User;
use App\TwoFactorAuthenticatable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RedirectIfTwoFactorAuthenticatable
{
    /**
     * The login rate limiter instance.
     *
     * @var App\LoginRateLimiter
     */
    protected $limiter;

    /**
     * Create a new controller instance.
     *
     * @param  App\LoginRateLimiter  $limiter
     * @return void
     */
    public function __construct(LoginRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if (optional($user)->two_factor_secret &&
            ! is_null(optional($user)->two_factor_confirmed_at) &&
            in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user))) {
            return $this->twoFactorChallengeResponse($request, $user);
        } else {
            return $next($request);
        }

    }

    /**
     * Attempt to validate the incoming credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function validateCredentials($request)
    {
        return tap(User::where('email', $request->email)->first(), function ($user) use ($request) {
            if (! $user || ! Auth::validate($request->only(['email', 'password']))) {
                $this->fireFailedEvent($request, $user);
                $this->throwFailedAuthenticationException($request);
            }

            if (config('hashing.rehash_on_login', true)) {
                Auth::getProvider()->rehashPasswordIfRequired($user, ['password' => $request->password]);
            }
        });
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException($request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return void
     */
    protected function fireFailedEvent($request, $user = null)
    {
        event(new Failed(Auth::guard('web')?->name, $user, [
            'email' => $request->email,
            'password' => $request->password,
        ]));
    }

    /**
     * Get the two factor authentication enabled response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function twoFactorChallengeResponse($request, $user)
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);
        TwoFactorAuthenticationChallenged::dispatch($user);

        return redirect()->route('two-factor.login');
    }
}
