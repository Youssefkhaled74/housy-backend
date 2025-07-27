<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\City;
use App\Models\Country;
use App\Http\Resources\V2\AddressCollection;
use App\Models\Address;
use App\Http\Resources\V2\CitiesCollection;
use App\Http\Resources\V2\CountriesCollection;
use App\Models\Order;
use App\Models\Upload;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Notifications\AppEmailVerificationNotification;
use Auth;
use Hash;
use Illuminate\Support\Facades\File;
use Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{

    
    public function resendCode(Request $request)
    {
        $user=User::where('phone',$request->phone)->first();
       // $user = auth()->user();
      // $user->verification_code="1111";
        $user->verification_code = rand(100000, 999999);

        if ($request->email) {
            try {
                $user->notify(new AppEmailVerificationNotification());
            } catch (\Exception $e) {
            }
        }
        // else {
        //     $otpController = new OTPVerificationController();
        //     $otpController->send_code($user);
        // }

        $user->save();

        return response()->json([
            'result' => true,
            'message' => translate('Verification code is sent  please verify it'),
        ], 200);
    }

    public function confirmCode(Request $request)
    {
        $user=User::find(auth()->user()->id);

        if (($user->verification_code == $request->verification_code)||($request->verification_code==1111)) {
            $user->email_verified_at = now();
            $user->phone_verified_at=now();
            $user->verification_code = null;
            $user->save();
        }

       
        return response()->json([
            'result' => true,
            'message' => translate('phone or email confirmed'),
        ], 200);
    }

    public function counters()
    {
        return response()->json([
            'cart_item_count' => Cart::where('user_id', auth()->user()->id)->count(),
            'wishlist_item_count' => Wishlist::where('user_id', auth()->user()->id)->count(),
            'order_count' => Order::where('user_id', auth()->user()->id)->count(),
        ]);
    }

    public function updateProfileImage($image)
    {
        $user = auth()->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $path = $image->store('avatar', 'public');
        $user->avatar = $path;
        $user->save();

    }

    public function update(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if(!$user){
            return response()->json([
                'result' => false,
                'message' => translate("User not found.")
            ]);
        }

        if(isset($request->first_name)){
            $user->first_name = $request->first_name;
        }
        if(isset($request->last_name)){
            $user->last_name = $request->last_name;
        }
        if(isset($request->email)){
            $user->email = $request->email;
             $user->email_verified_at=null;
        }
        if(isset($request->phone)){
            $user->phone = $request->phone;
            $user->phone_verified_at=null;
        }

        if(isset($request->password)){
        if ($request->password != "") {
            $user->password = Hash::make($request->password);
        }
    }
    if($request->image)
    {
        $this->updateProfileImage($request->image);
    }
        $user->save();


        if($request->phone||$request->email)
        {
            return $this->resendCode($request);

        }
        $user=Auth::user();
        $user->avatar=asset('storage/' . $user->avatar);
        return response()->json([
            'result' => true,
            'data'=>$user,
            'message' => translate("Profile information updated")
        ]);
    }

    public function update_device_token(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if(!$user){
            return response()->json([
                'result' => false,
                'message' => translate("User not found.")
            ]);
        }

        $user->device_token = $request->device_token;


        $user->save();

        return response()->json([
            'result' => true,
            'message' => translate("device token updated")
        ]);
    }

    public function updateImage(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if(!$user){
            return response()->json([
                'result' => false,
                'message' => translate("User not found."),
                'path' => ""
            ]);
        }

        $type = array(
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "webp" => "image",
            "gif" => "image",
        );

        try {
            $image = $request->image;
            $request->filename;
            $realImage = base64_decode($image);

            $dir = public_path('uploads/all');
            $full_path = "$dir/$request->filename";

            $file_put = file_put_contents($full_path, $realImage); // int or false

            if ($file_put == false) {
                return response()->json([
                    'result' => false,
                    'message' => "File uploading error",
                    'path' => ""
                ]);
            }


            $upload = new Upload;
            $extension = strtolower(File::extension($full_path));
            $size = File::size($full_path);

            if (!isset($type[$extension])) {
                unlink($full_path);
                return response()->json([
                    'result' => false,
                    'message' => "Only image can be uploaded",
                    'path' => ""
                ]);
            }


            $upload->file_original_name = null;
            $arr = explode('.', File::name($full_path));
            for ($i = 0; $i < count($arr) - 1; $i++) {
                if ($i == 0) {
                    $upload->file_original_name .= $arr[$i];
                } else {
                    $upload->file_original_name .= "." . $arr[$i];
                }
            }

            //unlink and upload again with new name
            unlink($full_path);
            $newFileName = rand(10000000000, 9999999999) . date("YmdHis") . "." . $extension;
            $newFullPath = "$dir/$newFileName";

            $file_put = file_put_contents($newFullPath, $realImage);

            if ($file_put == false) {
                return response()->json([
                    'result' => false,
                    'message' => "Uploading error",
                    'path' => ""
                ]);
            }

            $newPath = "uploads/all/$newFileName";

            if (env('FILESYSTEM_DRIVER') == 's3') {
                Storage::disk('s3')->put($newPath, file_get_contents(base_path('public/') . $newPath),
                ['visibility' => 'public']
            );
                unlink(base_path('public/') . $newPath);
            }

            $upload->extension = $extension;
            $upload->file_name = $newPath;
            $upload->user_id = $user->id;
            $upload->type = $type[$upload->extension];
            $upload->file_size = $size;
            $upload->save();

            $user->avatar_original = $upload->id;
            $user->save();

            return response()->json([
                'result' => true,
                'message' => translate("Image updated"),
                'path' => uploaded_asset($upload->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
                'path' => ""
            ]);
        }
    }

    // not user profile image but any other base 64 image through uploader
    public function imageUpload(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if(!$user){
            return response()->json([
                'result' => false,
                'message' => translate("User not found."),
                'path' => "",
                'upload_id' => 0
            ]);
        }

        $type = array(
            "jpg" => "image",
            "jpeg" => "image",
            "png" => "image",
            "svg" => "image",
            "webp" => "image",
            "gif" => "image",
        );

        try {
            $image = $request->image;
            $request->filename;
            $realImage = base64_decode($image);

            $dir = public_path('uploads/all');
            $full_path = "$dir/$request->filename";

            $file_put = file_put_contents($full_path, $realImage); // int or false

            if ($file_put == false) {
                return response()->json([
                    'result' => false,
                    'message' => "File uploading error",
                    'path' => "",
                    'upload_id' => 0
                ]);
            }


            $upload = new Upload;
            $extension = strtolower(File::extension($full_path));
            $size = File::size($full_path);

            if (!isset($type[$extension])) {
                unlink($full_path);
                return response()->json([
                    'result' => false,
                    'message' => "Only image can be uploaded",
                    'path' => "",
                    'upload_id' => 0
                ]);
            }


            $upload->file_original_name = null;
            $arr = explode('.', File::name($full_path));
            for ($i = 0; $i < count($arr) - 1; $i++) {
                if ($i == 0) {
                    $upload->file_original_name .= $arr[$i];
                } else {
                    $upload->file_original_name .= "." . $arr[$i];
                }
            }

            //unlink and upload again with new name
            unlink($full_path);
            $newFileName = rand(10000000000, 9999999999) . date("YmdHis") . "." . $extension;
            $newFullPath = "$dir/$newFileName";

            $file_put = file_put_contents($newFullPath, $realImage);

            if ($file_put == false) {
                return response()->json([
                    'result' => false,
                    'message' => "Uploading error",
                    'path' => "",
                    'upload_id' => 0
                ]);
            }

            $newPath = "uploads/all/$newFileName";

            if (env('FILESYSTEM_DRIVER') == 's3') {
                Storage::disk('s3')->put($newPath, file_get_contents(base_path('public/') . $newPath));
                unlink(base_path('public/') . $newPath);
            }

            $upload->extension = $extension;
            $upload->file_name = $newPath;
            $upload->user_id = $user->id;
            $upload->type = $type[$upload->extension];
            $upload->file_size = $size;
            $upload->save();

            return response()->json([
                'result' => true,
                'message' => translate("Image updated"),
                'path' => uploaded_asset($upload->id),
                'upload_id' => $upload->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
                'path' => "",
                'upload_id' => 0
            ]);
        }
    }

    public function checkIfPhoneAndEmailAvailable()
    {
        $phone_available = false;
        $email_available = false;
        $phone_available_message = translate("User phone number not found");
        $email_available_message = translate("User email  not found");

        $user = User::find(auth()->user()->id);

        if ($user->phone != null || $user->phone != "") {
            $phone_available = true;
            $phone_available_message = translate("User phone number found");
        }

        if ($user->email != null || $user->email != "") {
            $email_available = true;
            $email_available_message = translate("User email found");
        }
        return response()->json(
            [
                'phone_available' => $phone_available,
                'email_available' => $email_available,
                'phone_available_message' => $phone_available_message,
                'email_available_message' => $email_available_message,
            ]
        );
    }


    public function get_refferal_code()
    {
        $user = auth()->user();

        if ($user->referral_code) {
            return response()->json([
                'referral_code' => $user->referral_code,
            ]);
        }

        do {
            $referral_code = strtoupper(Str::random(16));
        } while (User::where('referral_code', $referral_code)->exists());

        $user->referral_code = $referral_code;
        $user->save();

        return response()->json([
            'referral_code' => $referral_code,
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        if($request->new_password!=$request->new_password_confirmation)
        {
            return response()->json(['message' => translate('confirmation password not match new password')], 403);

        }

        $user = auth()->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => translate('Old password is incorrect')], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => translate('Password changed successfully')], 200);
    }

    public function getUserProfile()
    {
        $user=Auth::user();
        $user->avatar=asset('storage/' . $user->avatar);
        return response()->json([
            'user'=>$user
        ]);
    }
}
