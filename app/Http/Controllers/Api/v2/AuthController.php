<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\Auth\LoginRequest;
use App\Models\PersonalAccessToken;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(LoginRequest $request)
    {
        try {
           $request->authenticate();
        } catch (\Throwable $e) {
            return $this->fail(
                401,
                __('auth.failed'),
                ['error' => $e->getMessage()]
            );
        }

        $user = Auth::guard('web')->user();

        if (!$user || $user->isDeactivated()) {
            return $this->fail(
                403,
                __('auth.user_inactive'),
                [
                    'user' => $user,
                ]
            );
        }

        PersonalAccessToken::where('tokenable_type', get_class($user))
            ->where('tokenable_id', $user->getKey())
            ->delete();

        $token = $user->createToken('api-v2')->plainTextToken;

        return $this->success(
            200,
            __('auth.user_logged_in'),
            [
                'token' => $token,
                'user' => $user,
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(
            200,
            __('auth.user_logged_out'),
            [
                'user' => $request->user(),
            ]
        );
    }
}
