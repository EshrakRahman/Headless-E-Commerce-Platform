<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and return an API token.
     *
     * @tags Authentication
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // Raw password is cast to 'hashed' automatically inside the User model casts() method
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('auth_token');

        return response()->json([
            'message' => 'Register successfully',
            'user' => $user,
            'token' => $token->plainTextToken,
        ], 201);
    }

    /**
     * Login and return an API token.
     *
     * @tags Authentication
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response([
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        $token = $user->createToken('auth_token');

        return response([
            'message' => 'You have successfully logged in.',
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Revoke the current API token.
     *
     * @tags Authentication
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response([
            'message' => 'You have successfully logged out.',
        ]);
    }
}
