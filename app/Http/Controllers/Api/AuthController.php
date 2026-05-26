<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;


class AuthController extends Controller
{
    function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string'
        ];

        $messages = [
            'email.required' => 'Email is required',
            'email.email' => 'Email is not valid',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return ApiMessage::error($validator->errors(), 400);
        }
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiMessage::error('Error', 'User or Password is incorrect', 401);
        }

        $user->tokens()->delete(); //untuk menghapus token login lama
        $token = $user->createToken('auth_token')->plainTextToken;
        $data = [
            'user' => $user,
            'token' => $token,
        ];

        return ApiMessage::success('Login successful', $data, 200);
    }

    public function registration(Request $request)
    {
        try {
            $rules = [
                'username' => 'required|string|unique:users',
                'email' => 'required|unique:users|email',
                'phone_number' => 'required|unique:users|string',
                'password' => 'required|string|min:10',
                'password_confirmation' => 'required|string|same:password',
            ];

            $messages = [
                'username.required' => 'Username is required',
                'username.unique' => 'Username already exists',
                'email.required' => 'Email is required',
                'email.email' => 'Email must be a valid email address',
                'email.unique' => 'Email already exists',
                'phone_number.required' => 'Phone number is required',
                'phone_number.unique' => 'Phone number already exists',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 10 characters',
                'password_confirmation.required' => 'Password confirmation is required',
                'password_confirmation.same' => 'Password confirmation must be matched with password',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return ApiMessage::error($validator->errors(), 400);
            }

            DB::beginTransaction();

            try {
                //save ke tabel user 
                $user = new User();
                $user->username = $request->username;
                $user->email = $request->email;
                $user->phone_number = $request->phone_number;
                $user->password = bcrypt($request->password);
                $user->save();

                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0
                ]);

                DB::commit();
                return ApiMessage::success('Success', 'Registration successful', 201);
            } catch (\Throwable $th) {
                DB::rollBack();
                return ApiMessage::error('Error', $th->getMessage(), 500);
            }
        } catch (\Throwable $th) {
            return ApiMessage::error('Error', $th->getMessage(), 500);
        }
    }

    public function index()
    {
        try {
            $users = User::all();
            return ApiMessage::success('Success get data', $users, 200);
        } catch (\Throwable $th) {
            return ApiMessage::error($th->getMessage(), 500);
        }
    }
}
