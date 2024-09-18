<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AttemptToAuthenticate;
use App\Actions\Auth\CanonicalizeUsername;
use App\Actions\Auth\EnsureLoginIsNotThrottled;
use App\Actions\Auth\PrepareAuthenticatedSession;
use App\Actions\Auth\RedirectIfTwoFactorAuthenticatable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Attempt to authenticate a new session.
     *
     * @return mixed
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        return $this->loginPipeline($request)->then(function ($request) {
            return redirect()->intended(route('dashboard', absolute: false));
        });
    }

    /**
     * Get the authentication pipeline instance.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Pipeline\Pipeline
     */
    protected function loginPipeline(LoginRequest $request)
    {
        return (new Pipeline(app()))->send($request)->through([
            EnsureLoginIsNotThrottled::class,
            CanonicalizeUsername::class,
            RedirectIfTwoFactorAuthenticatable::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
