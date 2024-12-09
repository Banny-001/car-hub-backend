<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|max:255|unique:users,email',
            'password' => 'required',

        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        //create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 'api_token'=> Str::radom(60),
        ]);
        // auth()->login($user);
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
        // $token = $user->createToken(name: 'main')->plainTextToken;

        // return response([
        //     'user' => $user,
        //     'token' => $token,
        // ]);
    }
    public function login(Request $request)
    {
        Log::info($request);

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => 'nullable'
        ]);
        Log::info("reached");

        $user = User::where('email', $request->email)->first();
        Log::info($user);
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $token = $user->createToken('auth')->plainTextToken;
        Log::info($token);
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ], 200);
    }
    // logout
    public function logout(Request $request)
    {
        // Revoke the user's current token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function sessionLogin(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::rWhere('email', $request->email)
            ->first();

        if (!$user) {
            return response(['message' => 'The provided credentials are incorrect.'], 401);
        }


        if (!Hash::check($request->password, $user->password)) {
            return response(['message' => 'The provided credentials are incorrect.'], 401);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json(['message' => 'Logged in successfully.'], 200);
    }
}
