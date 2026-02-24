<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v2\Auth\LoginRequest;
use App\Models\PersonalAccessToken;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Auth",
    description: "Authentication endpoints"
)]
class AuthController extends Controller
{
    use ApiResponseTrait;

    #[OA\Post(
        path: "/auth/login",
        summary: "Login user",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful login",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "token", type: "string", example: "1|abcdef123456..."),
                            new OA\Property(property: "user", type: "object")
                        ]),
                        new OA\Property(property: "message", type: "string", example: "User successfully logged in.")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "User inactive")
        ]
    )]
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

    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout user",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful logout",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "User successfully logged out.")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
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
