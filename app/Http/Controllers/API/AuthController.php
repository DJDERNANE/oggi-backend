<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{


    public function register(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hash password
            ]);

            // Generate access token
            $token = $user->createToken('authToken')->accessToken;

            return response()->json([
                'message' => 'User registered successfully',
                'token' => $token,
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Retrieve user manually for debugging
        $user = User::where('email', $credentials['email'])->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'debug' => $credentials['email']
            ], 404);
        }
    
        // Check if password matches manually
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Incorrect password',
                'debug' => [
                    'entered_password' => $credentials['password'],
                    'hashed_password_in_db' => $user->password
                ]
            ], 401);
        }
    
        // If manual check works but Auth::attempt fails, problem is with guard/session
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Auth::attempt failed',
                'debug' => [
                    'guard' => config('auth.defaults.guard'),
                    'email' => $credentials['email'],
                ]
            ], 401);
        }
    
        $user = Auth::user();
        $token = $user->createToken('authToken');
    
        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request)
    {
        $token = $request->user()->createToken('authToken')->accessToken;
        return response()->json(['token' => $token]);
    }
}
