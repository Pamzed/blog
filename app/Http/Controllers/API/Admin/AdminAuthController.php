<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OTPMail;
use App\Models\Admins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AdminAuthController extends Controller
{

    /**
     * Returns as success with json response
     * 
     * @return string
     */
    private function genSixDigits()
    {
        $pin = range(0, 9);
        $set = shuffle($pin);
        $sixDigits = "";
        for ($i = 0; $i < 4; $i++) {
            $sixDigits = $sixDigits . "" . $pin[$i];
        }
        return $sixDigits;
    }

    /**
     * Returns as success with json response
     * @return mixed
     */
    private function object_to_array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = (is_array($value) || is_object($value)) ? $this->object_to_array($value) : $value;
            }
            return $result;
        }
        return $data;
    }


    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request): JsonResponse
    {
        if (is_null($request->email)) {
            return $this->error('Email cannot be empty.');
        } else if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email is not valid.');
        } else if (is_null($request->password)) {
            return $this->error('Password cannot be empty.');
        }


        // checking if admin email is in the record and validate password
        $admin = Admins::where('email', $request->email)->first();

        if (is_null($admin)) {
            return $this->error('This Email is not registered as an Admin.');
        }

        if (!Hash::check($request->password, $admin->password)) {
            return $this->error('Password passed is not valid for this account.');
        }

        Admins::where('email', $request->email)->update([
            'last_login' => now(),
        ]);

        $token = $admin->createToken('token')->plainTextToken;

        $details = $admin->makeHidden([
            'id',
            'created_at',
            'updated_at',
        ]);

        $data = [
            'admin_details' => $details,
            'token' => $token,
        ];

        return $this->success($data, 'Admin Authenticated');
    }

    /**
     * Returns as success with json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        return $this->systemSendCode($request->email);
    }

    /**
     * Returns as success with json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        if (is_null($request->otp_code) || empty($request->otp_code) || $request->otp_code == ' ') {
            return $this->error('Verification code cannot be empty or null');
        }

        if (is_null($request->new_password) || empty($request->new_password) || $request->new_password == ' ') {
            return $this->error('Password cannot be empty or null');
        }

        $vCode = Admins::where('otp_code', $request->otp_code)->first();

        if (is_null($vCode)) {
            return $this->error('Verification code passed does not exist');
        }

        if ($vCode->otp_code != $request->otp_code) {
            return $this->error('Verification code does not match');
        }

        if (now() > $vCode->otp_code_expiring) {
            return $this->error('Verification code has expired');
        }

        Admins::where('id', $vCode->id)->update([
            'password' => Hash::make($request->new_password),
            'otp_code' => null,
        ]);

        return $this->success(null, 'Password successfully changed.');
    }

    /**
     * Returns as success with json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendCode(Request $request): JsonResponse
    {
        return $this->systemSendCode($request->email);
    }


    /**
     * Returns as success with json response
     * 
     * @param string $email
     * @param mixed $from
     * @return JsonResponse
     */
    public function systemSendCode($email, $from = null): JsonResponse
    {
        if (is_null($email) || empty($email) || $email == ' ') {
            return $this->error('Email cannot be empty or null');
        }

        $user = Admins::where('email', $email)->first();

        if (is_null($user)) {
            return $this->error('Account does not exist.', 404);
        }

        $details = [
            'otp_code' => $this->genSixDigits(),
        ];

        Admins::where('id', $user->id)->update([
            'otp_code' => $details['otp_code'],
            'otp_code_expiring' => Carbon::now()->addMinutes(5),
        ]);

        Mail::to($email)->queue(new OTPMail($details));

        return $this->success(null, 'Email Sent');
    }
}
