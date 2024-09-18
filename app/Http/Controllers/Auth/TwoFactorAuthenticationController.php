<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\DisableTwoFactorAuthentication;
use App\Actions\Auth\EnableTwoFactorAuthentication;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Enable two factor authentication for the user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable($request->user(), $request->boolean('force', false));

        return back()->with('status', 'two-factor-authentication-enabled');
    }

    /**
     * Disable two factor authentication for the user.
     *
     * @param  App\Actions\Auth\DisableTwoFactorAuthentication  $disable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable($request->user());

        return back()->with('status', 'two-factor-authentication-disabled');
    }
}
