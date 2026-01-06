<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Driver;
use App\Models\DriverLoginData;

class RegisterController extends BaseController
{
    // Register api
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('MyApp')->plainTextToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    // Login api
    public function login(Request $request): JsonResponse
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            /** @var \App\Models\User */
            $user = Auth::user();
            $success['token'] =  $user->createToken('MyApp')->plainTextToken;
            $success['name'] =  $user->name;

            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }

    // Login api
    public function driverLogin(Request $request): JsonResponse
    {
        $request->validate([
            'phone_number' => 'required|string',
            'token'        => 'required|string',
        ]);

        $driver = Driver::where('phone', $request->phone_number)
            ->where('login_token', $request->token)
            ->first();

        if ($driver) {
            $plainToken = $driver->createToken('DriverAPP')->plainTextToken;

            $success = [
                'token' => $plainToken,
                'name'  => $driver->name,
            ];

            DriverLoginData::create([
                'driver_id'   => $driver->id,
                'token'       => $plainToken,
                'login_date'  => now(),
                'expired_date' => now()->addHours(2),
                'status'      => 'active',
                'created_by'  => $driver->id,
            ]);

            return $this->sendResponse($success, 'Driver login successfully.');
        }

        return $this->sendError('Unauthorised.', ['error' => 'Unauthorised']);
    }
}
