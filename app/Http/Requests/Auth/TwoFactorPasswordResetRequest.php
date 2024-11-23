<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class TwoFactorPasswordResetRequest extends TwoFactorLoginRequest
{
    /**
     * The user attempting the two factor challenge.
     *
     * @var mixed
     */
    protected $challengedUser;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
            'token' => 'required',
            'email' => 'required|email',
        ];
    }

    /**
     * Determine if there is a challenged user in the current session.
     *
     * @return bool
     */
    public function hasChallengedUser()
    {
        if ($this->challengedUser) {
            return true;
        }

        return User::where('email', $this->email)->exist();
    }

    /**
     * Get the user that is attempting the two factor challenge.
     *
     * @return mixed
     */
    public function challengedUser()
    {
        if ($this->challengedUser) {
            return $this->challengedUser;
        }

        if (! $user = User::where('email', $this->email)->sole()) {
            throw new HttpResponseException(
                $this->failedTwoFactorLoginResponse()
            );
        }

        return $this->challengedUser = $user;
    }
}
