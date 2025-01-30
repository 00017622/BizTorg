<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
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
            'token' => $token,
            'status' => 'success',
            'message' => 'Successfully logged in',
        ], 200);
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
            'name' => 'Имя и фамилия',
        ]);

        $token = $createdUser->createToken('Auth-Api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'uuid' => $createdUser->id,
            'status' => 'success',
            'message' => 'successfully logged in',
        ], 201);
    }
}