<?php

namespace App\Http\Controllers;

use App\User;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthenticationController extends Controller
{
    //this method adds new users
    public function createAccount(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'nullable|unique:users|max:255',
            'first_name' => 'required|max:255',
            'middle_name' => 'nullable|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'phone_number' => 'nullable|unique:users',
            'emergency_contact' => 'nullable|unique:users',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password',
            'ndis_participant' => 'required|boolean',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }
        
        $data = $request->except('confirm_password');
        $data['password'] = Hash::make($request->password);
        $user = User::create($data);

        if ($user) {
            $token = $user->createToken('visible')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => "Registration Success.",
            ]);
        }

        return response()->json([
            "message" => "Registration Failed.",
        ], 400);
    }
    //use this method to signin users
    public function signin(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $field = (filter_var(request()->email, FILTER_VALIDATE_EMAIL) || !request()->email) ? 'email' : 'name';
        $email = $request->email;
         
        if($field == 'name') {
            $user = User::where('name', $email)->first();
            if($user) {
                $email = $user->email;
            } else {
                return response()->json([
                    "message" => "Username mismatch.",
                ], 401);
            }
        }
        $attemptLogin = Auth::attempt(['email' => $email, 'password' => $request->password]);

        if ($attemptLogin) {
            $token = Auth::user()->createToken('visible')->plainTextToken;
            return response()->json([
                'user' => Auth::user(),
                'token' => $token,
                'message' => "Login Success.",
            ]);
        }

        return response()->json([
            "message" => "Username or Password mismatch.",
        ], 401);
    }

    // this method signs out users by removing tokens
    public function signout()
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            "message" => "User Logged Out.",
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $data = $request->except('email', 'password', 'name', 'avatar');
        if(!empty($request->file('avatar'))) {
            $path = $this->uploadImage($request->file('avatar'));
            $data['avatar'] = $path;
        }
        $user->update($data);

        return response()->json([
            "message" => "User Profile Updated.",
        ]);
    }

    public function uploadImage($img)
    {
        $fileName = time().'.'.$img->extension();  
        $fileOrigName = $img->getClientOriginalName();
        $fileSize = $img->getSize();
        $path = $img->storeAs('public/avatars',$fileName);

        return $path;
    }

    public function forgotPassword(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $credentials = $request->only('email');

        $token = $this->sendResetToken($credentials);

        return response()->json(["msg" => 'Reset password token.', 'link' => $token]);
    }

    public function passwordReset(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'required',
            'token' => 'required',
            'password' => 'required',
        ]);
        if ($validate->fails()) {
            return response([
                'message' => $validate->errors(),
            ], 422);
        }

        $credentials = $request->password;
        $token = DB::table('password_resets')->whereEmail($request->email)->first();
        if($token->token!=$request->token) {
            return response()->json([
                "message" => "Invalid Token.",
            ], 400);
        }

        $user = User::whereEmail($request->email)->orWhere('name', 'LIKE', $request->email)->update([
            'password' => Hash::make($credentials)
        ]);

        if ($user) {
            DB::table('password_resets')->whereEmail($request->email)->delete();
            return response()->json([
                'message' => "Password Updated.",
            ]);
        }

        return response()->json([
            "message" => "Failed Resetting.",
        ], 400);
    }

    private function sendResetToken($credentials) {
        $token_exists = DB::table('password_resets')->whereEmail($credentials['email'])->first();
        $token = rand(2500, 10000);
        if($token_exists) {
            DB::table('password_resets')->whereEmail($credentials['email'])->update([
                'email' => $credentials['email'],
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);
        } else {
            DB::table('password_resets')->insert([
                'email' => $credentials['email'],
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);
        }

        return $token;
    }

    public function reset() {
        $credentials = request()->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);

        $reset_password_status = Password::reset($credentials, function ($user, $password) {
            $user->password = $password;
            $user->save();
        });

        if ($reset_password_status == Password::INVALID_TOKEN) {
            return response()->json(["msg" => "Invalid token provided"], 400);
        }

        return response()->json(["msg" => "Password has been successfully changed"]);
    }

}
