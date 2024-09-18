<?php

namespace App\Actions\Auth;

use Illuminate\Support\Str;

class CanonicalizeUsername
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $request->merge([
            'email' => Str::lower($request->email),
        ]);

        return $next($request);
    }
}
