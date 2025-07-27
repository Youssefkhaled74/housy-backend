<?php

namespace App\Http\Controllers\Api\V2;

use App\Notifications\AppEmailVerificationNotification;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordReset;
use App\Notifications\PasswordResetRequest;
use Illuminate\Support\Str;
use App\Http\Controllers\OTPVerificationController;

use Hash;

class PasswordResetController extends Controller
{
    public function forgetRequest(Request $request)
    {
        // if ($request->send_code_by == 'email') {
        //     $user = User::where('email', $request->email_or_phone)->first();
        // } else {
        //     $user = User::where('phone', $request->email_or_phone)->first();
        // }
        $user = User::where('phone', $request->phone)->first();


        if (!$user) {
            return response()->json([
                'result' => false,
                'message' => translate('User is not found')
            ], 404);
        }

        if ($user) {
            //$user->verification_code = rand(100000, 999999);
            $user->verification_code ="1111";
            $user->save();
            if ($request->send_code_by == 'phone') {

                // $otpController = new OTPVerificationController();
                // $otpController->send_code($user);
            } else {
                try {

                    $user->notify(new AppEmailVerificationNotification());
                } catch (\Exception $e) {
                }
            }
        }

        return response()->json([
            'result' => true,
            'message' => translate('A code is sent')
        ], 200);
    }

    public function confirmReset(Request $request)
    {
        $user = User::where('verification_code', $request->verification_code)->first();

            if(!$user)
        {
             return response()->json([
                'result' => false,
                'message' => translate('verification code  incorrect')
            ], 404);
        }
      

        if ($user != null) {
            if(!$user->password_verified_at)
            {
                return response()->json([
                'result' => false,
                'message' => translate(' code  not verified')
            ], 404);
            }
          
            $user->verification_code = null;
            $user->password_verified_at = null;
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'result' => true,
                'message' => translate('Your password is reset.Please login'),
            ], 200);
        } 
    }

    public function resendCode(Request $request)
    {

        
            $user = User::where('phone', $request->phone)->first();
       

        if (!$user) {
            return response()->json([
                'result' => false,
                'message' => translate('User is not found')
            ], 404);
        }
        $verify=User::where('verification_code', $request->verification_code)->first();
        if(!$verify)
        {
             return response()->json([
                'result' => false,
                'message' => translate('verification code incorrect')
            ], 404);
        }
        $user->password_verified_at=now();
        $user->save();

      

        //$user->verification_code = rand(100000, 999999);
        // $user->verification_code = "1111";
        // $user->save();

        // if ($request->verify_by == 'email') {
        //     $user->notify(new AppEmailVerificationNotification());
        // } 
        
        
        // else {
        //     $otpController = new OTPVerificationController();
        //     $otpController->send_code($user);
        // }



        return response()->json([
            'result' => true,
            'message' => translate('code verified'),
        ], 200);
    }
}
