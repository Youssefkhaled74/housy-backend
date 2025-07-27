<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;

use Laravel\Sanctum\PersonalAccessToken;


class UserController extends Controller
{
    public function info($id)
    {
        return new UserCollection(User::where('id', auth()->user()->id)->get());
    }

    public function updateName(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->update([
            'name' => $request->name
        ]);
        return response()->json([
            'message' => translate('Profile information has been updated successfully')
        ]);
    }

   
    public function getUserInfoByAccessToken(Request $request)
    {
        $false_response = [
            'result' => false,
            'id' => 0,
            'first_name' => "",
            'last_name' => "",
            'email' => "",
            'avatar' => "",
            'avatar_original' => "",
            'phone' => ""
        ];

        // Validate the access token
        $token = PersonalAccessToken::findToken($request->access_token);
        if (!$token) {
            return response()->json([
                'result' => false,
                'message' => translate("token not found")
            ],400);
        }

        $user = $token->tokenable;

        // If no user is linked to the token
        if (!$user) {
             return response()->json([
                'result' => false,
                'message' => translate("user not found")
            ],400);
        }

        // Success response
        return response()->json([
            'result' => true,
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_original' => uploaded_asset($user->avatar_original),
            'phone' => $user->phone
        ]);
    }
}
