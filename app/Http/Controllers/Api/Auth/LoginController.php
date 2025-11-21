<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ApiResponseTrait;

    /**
     * Handle member login via Passport tokens.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials provided.', 401);
        }

        if (!$user->hasRole('member')) {
            return $this->unauthorizedResponse('Only members can access this API.');
        }

        if (!$user->isActive()) {
            return $this->unauthorizedResponse('Your account is inactive.');
        }

        $tokenResult = $user->createToken('member-api');

        return $this->tokenResponse('Login successful', $tokenResult->accessToken, [
            'token_type' => 'Bearer',
            'expires_at' => optional($tokenResult->token->expires_at)->toDateTimeString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }
}

