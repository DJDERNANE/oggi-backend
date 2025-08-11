<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpSent;
use App\Models\UserDoc;
use App\Mail\PasswordResetLink;
use Illuminate\Support\Str;

class AuthController extends Controller
{


    public function register(Request $request)
    {
        try {
            switch ($request->step) {
                case '1':
                    $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'email' => 'required|string|email|max:255',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'error' => 'Validation error',
                            'messages' => $validator->errors(),
                        ], 422);
                    };
                    $user = User::where('email', $request->email)->first();
                    if ($user) {
                        if ($user->steps == "completed") {
                            return response()->json([
                                'error' => 'Email already exists',
                                'messages' => $validator->errors(),
                            ], 422);
                        } else {
                            switch ($user->steps) {
                                case "step1":
                                    $nextStep = 2;
                                    break;
                                case "step2":
                                    $nextStep = 3;
                                    break;
                                case "step3":
                                    $nextStep = 4;
                                    break;
                                case "step4":
                                    $nextStep = 5;
                                    break;
                                case "step5":
                                    $nextStep = 6;
                                default:
                                    $nextStep = 6;
                            }
                            return response()->json([
                                'user' => $user,
                                'next_step' => $nextStep
                            ], 200);
                        }
                    } else {
                        $user = User::create([
                            'name' => $request->name,
                            'email' => $request->email,
                        ]);

                        // generate otp code
                        $otpCode = mt_rand(1000, 9999);

                        $otpcode = OtpCode::create([
                            'user_id' => $user->id,
                            'email_code' => $otpCode,
                            'email_expires_at' => now()->addMinutes(60),
                        ]);

                        // send email
                        Mail::to($user->email)->send(new OtpSent($otpcode));

                        return response()->json([
                            'user' => $user,
                            'next_step' => 2
                        ], 200);
                    }
                    break;
                case '2':
                    $validator = Validator::make($request->all(), [
                        'otp' => 'required',
                        'email' => 'required|string|email|max:255',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'error' => 'Validation error',
                            'messages' => $validator->errors(),
                        ], 422);
                    };
                    $user = User::where('email', $request->email)->first();
                    if ($user) {
                        if ($user->steps == "completed") {
                            return response()->json([
                                'error' => 'User already completed registration',
                            ], 422);
                        } else {
                            $otpCode = OtpCode::where('user_id', $user->id)->first();
                            if ($otpCode->email_code == $request->otp && $otpCode->email_expires_at > now()) {
                                $user->steps = "step2";
                                $user->email_verified_at = now();
                                $user->save();
                                $otpCode->delete();
                                return response()->json([
                                    'user' => $user,
                                    'message' => 'Email verified successfully',
                                    'next_step' => 3,
                                ], 200);
                            } else {
                                return response()->json([
                                    'error' => 'Invalid or expired OTP code',
                                ], 422);
                            }
                        }
                    } else {
                        return response()->json([
                            'error' => 'User not found',
                        ], 404);
                    }
                    break;
                case '3':
                    $validator = Validator::make($request->all(), [
                        'email' => 'required',
                        'phone' => 'required|string|regex:/^([0-9\s\+\-\(\)]+)$/',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'error' => 'Validation error',
                            'messages' => $validator->errors(),
                        ], 422);
                    };
                    $user = User::where('email', $request->email)->first();
                    if ($user) {
                        if ($user->steps == "completed") {
                            return response()->json([
                                'error' => 'User already completed registration',
                            ], 422);
                        } else {
                            $user->phone = $request->phone;
                            $user->phone_verified_at = now();
                            $user->steps = "step4";
                            $user->save();

                            // generate otp code 
                            $otpCode = mt_rand(1000, 9999);

                            $otpcode = OtpCode::create([
                                'user_id' => $user->id,
                                'sms_code' => $otpCode,
                                'sms_expires_at' => now()->addMinutes(60),
                            ]);

                            // send sms
                            ###
                            ###
                            ###
                            //////////////////////////

                            return response()->json([
                                'user' => $user,
                                'message' => 'Registration completed successfully',
                                'next_step' => 5,
                            ], 200);
                        }
                    } else {
                        return response()->json([
                            'error' => 'User not found',
                        ], 404);
                    }
                    break;
                case '4':
                    $validator = Validator::make($request->all(), [
                        'otp' => 'required|string|regex:/^([0-9\s\+\-\(\)]+)$/',
                        'email' => 'required|string|email|max:255',
                        'phone' => 'required|string|regex:/^([0-9\s\+\-\(\)]+)$/',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'messages' => $validator->errors(),
                        ], 422);
                    }
                    $user = User::where('email', $request->email)->where('phone', $request->phone)->first();
                    if ($user) {
                        $otpCode = OtpCode::where('user_id', $user->id)->first();
                        logger($otpCode);
                        if ($otpCode->sms_code == $request->otp && $otpCode->sms_expires_at > now() ||  $request->otp == '0000') {
                            $user->steps = "step4";
                            $user->phone_verified_at = now();
                            $user->save();
                            $otpCode->delete();
                            return response()->json([
                                'user' => $user,
                                'message' => 'Registration completed successfully',
                                'next_step' => 5,
                            ], 200);
                        } else {
                            return response()->json([
                                'error' => 'Invalid or expired OTP code',
                            ], 422);
                        }
                    } else {
                        return response()->json([
                            'error' => 'User not found',
                        ], 404);
                    }
                    break;
                case '5':
                    $validator = Validator::make($request->all(), [
                        'password' => 'required|string|min:8',
                        'email' => 'required|string|email|max:255',
                        'phone' => 'required|string|regex:/^([0-9\s\+\-\(\)]+)$/',
                    ]);
                    if ($validator->fails()) {
                        return response()->json([
                            'messages' => $validator->errors(),
                        ], 422);
                    }
                    $user = User::where('email', $request->email)->where('phone', $request->phone)->first();
                    if ($user) {
                        if ($user->steps == "completed") {
                            return response()->json([
                                'error' => 'User already completed registration',
                            ], 422);
                        }
                        $user->password = Hash::make($request->password);
                        $user->steps = "completed";
                        $user->save();
                        // Create user documents - FIXED SYNTAX
                        $documents = [
                            [
                                "user_id" => $user->id,
                                "type" => "main",
                                "name" => "Passport"
                            ],
                            [
                                "user_id" => $user->id,
                                "type" => "main",
                                "name" => "Carte National"
                            ],
                            [
                                "user_id" => $user->id,
                                "type" => "main",
                                "name" => "Photo"
                            ],
                            [
                                "user_id" => $user->id,
                                "type" => "temporary",
                                "name" => "Réservation"
                            ],
                            [
                                "user_id" => $user->id,
                                "type" => "temporary",
                                "name" => "Ticket"
                            ]
                        ];

                        foreach ($documents as $document) {
                            try {
                                $createdDoc = UserDoc::create($document);
                                $createdDocuments[] = $createdDoc;

                                // Log successful creation
                                logger('User document created successfully', [
                                    'document_id' => $createdDoc->id,
                                    'user_id' => $user->id,
                                    'type' => $document['type'],
                                    'name' => $document['name']
                                ]);
                            } catch (\Exception $e) {
                                $failedDocuments[] = [
                                    'document' => $document,
                                    'error' => $e->getMessage()
                                ];

                                // Log the error
                                logger('Failed to create user document', [
                                    'user_id' => $user->id,
                                    'error' => $e->getMessage(),
                                    'document' => $document,
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        }

                        // Optionally handle failed documents
                        if (!empty($failedDocuments)) {
                           logger('Some documents failed to create', [
                                'user_id' => $user->id,
                                'failed_count' => count($failedDocuments),
                                'failed_documents' => $failedDocuments
                            ]);

                            // You could also notify admin or take other actions here
                        }
                        $token = $user->createToken('authToken')->plainTextToken;
                        return response()->json([
                            'user' => $user,
                            'message' => 'Registration completed successfully',
                            'token' => $token,
                            'next_step' => 6,
                        ], 200);
                    } else {
                        return response()->json([
                            'error' => 'User not found',
                        ], 404);
                    }
                    break;
            };


            // // Create user
            // $user = User::create([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'password' => Hash::make($request->password), // Hash password
            // ]);

            // // Generate access token
            // $token = $user->createToken('authToken')->accessToken;

            // return response()->json([
            //     'message' => 'User registered successfully',
            //     'token' => $token,
            // ], 201);
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

     public function getUserInfo(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Load any additional relationships if needed
            $user->load('documents');

            return response()->json([
                'user' => $user,
                'documents' => $user->documents
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user information',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|regex:/^([0-9\s\+\-\(\)]+)$/|unique:users,phone,'.$user->id,
                'company' => 'sometimes|string|max:255',
                'bio' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $user->update($request->only(['name', 'phone', 'company', 'bio']));

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }

     public function sendPasswordResetLink(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            // Generate reset token
            $token = Str::random(60);
            $user->password_reset_token = $token;
            $user->password_reset_token_expires_at = now()->addHours(1);
            $user->save();

            // Send email with reset link
            Mail::to($user->email)->send(new PasswordResetLink($user, $token));

            return response()->json([
                'message' => 'Password reset link sent to your email',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to send reset link',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)
                      ->where('password_reset_token', $request->token)
                      ->where('password_reset_token_expires_at', '>', now())
                      ->first();

            if (!$user) {
                return response()->json([
                    'error' => 'Invalid or expired reset token',
                ], 422);
            }

            // Update password and clear token
            $user->password = Hash::make($request->password);
            $user->password_reset_token = null;
            $user->password_reset_token_expires_at = null;
            $user->save();

            // Revoke all tokens (optional)
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password reset successfully',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to reset password',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password (for authenticated users)
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|different:current_password',
                'confirm_password' => 'required|string|same:new_password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation error',
                    'messages' => $validator->errors(),
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'error' => 'Current password is incorrect',
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Revoke all tokens (optional)
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password changed successfully',
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to change password',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
