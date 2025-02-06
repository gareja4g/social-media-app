<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends BaseController
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'user_name' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', 422, $validator->errors());
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return $this->sendSuccess($user, 'User registered successfully', 201);
        } catch (\Exception $e) {
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred during registration. Please try again later.', 500);
        }
    }

    /**
     * Login user and generate an API token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $rules = [
                'email' => 'required|email',
                'password' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', 422, $validator->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->sendError('Invalid credentials', 401);
            }

            // Generate the API token
            $token = $user->createToken('API Token')->accessToken;

            return $this->sendSuccess([
                "user" => $user,
                'token' => $token
            ], 'Login successful');
        } catch (\Exception $e) {
            Log::error('User login failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred during login. Please try again later.', 500);
        }
    }

    /**
     * Logout the currently authenticated user.
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            Auth::user()->token()->revoke();
            return $this->sendSuccess([], 'Logged out successfully');
        } catch (\Exception $e) {
            Log::error('User logout failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred during logout. Please try again later.', 500);
        }
    }
}
