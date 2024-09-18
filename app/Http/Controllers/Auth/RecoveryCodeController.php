<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GenerateNewRecoveryCodes;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RecoveryCodeController extends Controller
{
    /**
     * Get the two factor authentication recovery codes for authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! $request->user()->two_factor_secret ||
            ! $request->user()->two_factor_recovery_codes) {
            return [];
        }

        return response()->json(json_decode(decrypt(
            $request->user()->two_factor_recovery_codes
        ), true));
    }

    /**
     * Generate a fresh set of two factor authentication recovery codes.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, GenerateNewRecoveryCodes $generate)
    {
        $generate($request->user());

        return back()->with('status', 'recovery-codes-generated');
    }
}
