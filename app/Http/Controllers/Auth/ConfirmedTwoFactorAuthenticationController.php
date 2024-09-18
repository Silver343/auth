<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConfirmTwoFactorAuthentication;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConfirmedTwoFactorAuthenticationController extends Controller
{
    /**
     * Enable two factor authentication for the user.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $confirm($request->user(), $request->input('code'));

        return back()->with('status', 'two-factor-authentication-confirmed');
    }
}
