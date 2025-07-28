<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\TempCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Mail\VerificationCodeMail;
use Exception;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;
use League\Flysystem\ResolveIdenticalPathConflict;

class CustomLoginController extends Controller {
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'The user with this email not found',
                'status' => 'error',
            ], 404);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid password',
                'status' => 'error',
            ], 401);
        }

        $token = $user->createToken('Auth-Api')->plainTextToken;

        return response()->json([
            'uuid' => $user->id,
            'status' => 'success',
            'message' => 'Successfully logged in',
        ], 200)->cookie(
    'auth_token',
    $token,
    60 * 24 * 90,     // 90 days
    '/',              // path
    'localhost',      // domain: must match Nuxt (localhost)
    false,            // secure: false for HTTP in local
    true,             // httpOnly
    false,            // raw
    'Lax'             // or 'None' if you want aggressive cross-origin
        );
    }


    public function register(Request $request) {
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ]);

       if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation is incorrect',
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
       }

       $validated = $validator->validated();

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            return response()->json([
                'message' => 'User with such email already exists',
            ], 409);
        }

        $createdUser = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => '0',
        ]);

        $token = $createdUser->createToken('Auth-Api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'uuid' => $createdUser->id,
            'status' => 'success',
            'message' => 'successfully logged in',
        ], 201);
    }

    public function sendVerificationCode(Request $request)
    {
        Log::debug('Received request to send verification code', ['email' => $request->input('email')]);

        // Validate the email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for send verification code', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');

        // Generate a 8-character code with numbers, letters, and symbols
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%&';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        Log::debug('Generated verification code', ['email' => $email, 'password' => $password]);

        // Set expiration time (15 minutes from now)
        $expiresAt = now()->addMinutes(15);

        // Store or update the temp credential
        try {
            TempCredential::updateOrCreate(
                ['email' => $email],
                [
                    'password' => $password,
                    'expires_at' => $expiresAt,
                ]
            );
            Log::info('Stored temp credential', ['email' => $email]);
        } catch (\Exception $e) {
            Log::error('Failed to store temp credential', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to store verification code',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Send the email
        try {
            // Log email sending attempt
            Log::debug('Attempting to send verification email', [
                'email' => $email,
                'mailer' => config('mail.mailer'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
            ]);

            Mail::to($email)->send(new VerificationCodeMail($password, $email));

            Log::info('Verification email sent successfully', ['email' => $email]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to send email',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Verification code sent successfully',
        ], 200);
    }

    public function verifyAndRegister(Request $request)
    {
        Log::debug('Received request to verify and register', ['email' => $request->input('email')]);

        // Validate the email and password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|size:8',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for verify and register', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'message' => 'Неправильный пароль или email',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');

        // Check if the email exists in TempCredential
        $tempCredential = TempCredential::where('email', $email)->first();

        if (!$tempCredential) {
            Log::warning('Email not found in temp credentials', ['email' => $email]);
            return response()->json([
                'message' => 'Данный email не был найден',
            ], 404);
        }

        // Check if the password matches
        if ($tempCredential->password !== $password) {
            Log::warning('Invalid verification code', ['email' => $email]);
            return response()->json([
                'message' => 'Введен неправильный сгенерированный пароль',
            ], 401);
        }

        // Check if the code has expired
        if (now()->greaterThan($tempCredential->expires_at)) {
            Log::warning('Verification code has expired', ['email' => $email, 'expires_at' => $tempCredential->expires_at]);
            return response()->json([
                'message' => 'Данный пароль истек',
            ], 410);
        }

        // Check if the user already exists in the users table
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            Log::warning('User already exists', ['email' => $email]);
            return response()->json([
                'message' => 'Данный email уже испльзуется',
            ], 409);
        }

        // Create a new user
        try {
            $newUser = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => '0',
            ]);
            Log::info('New user created', ['email' => $email, 'user_id' => $newUser->id]);
        } catch (\Exception $e) {
            Log::error('Failed to create new user', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Generate an API token for the new user
        $token = $newUser->createToken('Auth-Api')->plainTextToken;

        // Delete the TempCredential record
        try {
            $tempCredential->delete();
            Log::info('Temp credential deleted after successful registration', ['email' => $email]);
        } catch (\Exception $e) {
            Log::error('Failed to delete temp credential', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            // We won't fail the request if deletion fails, just log the error
        }

        return response()->json([
           
            'uuid' => $newUser->id,
            'status' => 'success',
            'message' => 'Успешно зарегестрированы!',
        ], 201)->cookie(
    'auth_token',
    $token,
    60 * 24 * 90,     // 90 days
    '/',              // path
    'localhost',      // domain: must match Nuxt (localhost)
    false,            // secure: false for HTTP in local
    true,             // httpOnly
    false,            // raw
    'Lax'             // or 'None' if you want aggressive cross-origin
        );
    }



    public function storeFcmToken(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'fcm_token' => 'required|string',
    ]);

    try {
        $user = User::findOrFail($request->user_id);
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'FCM token updated successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to update FCM token',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function clearFcmToken(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    try {
        $user = User::findOrFail($request->user_id);
        $user->fcm_token = null;
        $user->save();

        return response()->json([
            'message' => 'FCM token cleared successfully',
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to clear FCM token',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'name' => $user->name,
        ], 200);
    }

        public function getFcmToken($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['fcm_token' => $user->fcm_token], 200);
    }
}