<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\WalletCollection;
use App\Models\ClubPoint;
use App\Models\CombinedOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function balance()
    {
        $user = User::find(auth()->user()->id);

        $user_points = 0;
        
        if ($user) {
            $club_points = ClubPoint::where('user_id', $user->id)->get();
            foreach ($club_points as $club_point) {
                $user_points += $club_point->club_point_details->where('refunded', 0)->sum('point');
            }
        }
        
        $latest = Wallet::where('user_id', auth()->user()->id)->latest()->first();
        return response()->json([
            'balance' => single_price($user->balance),
            'history'=> new WalletCollection(Wallet::where('user_id', auth()->user()->id)->get()),
            'last_recharged' => $latest == null ? "Not Available" : $latest->created_at->diffForHumans(),
            'points'=>$user_points
        ]);
    }

    public function walletRechargeHistory()
    {
        return new WalletCollection(Wallet::where('user_id', auth()->user()->id)->latest()->paginate(10));
    }

    public function processPayment(Request $request)
    {
        $order = new OrderController;
        $user = User::find(auth()->user()->id);

        if ($user->balance >= $request->amount) {
            
            $response =  $order->store($request, true);
            $decoded_response = $response->original;
            if ($decoded_response['result'] == true) { // only decrease user balance with a success
                $user->balance -= $request->amount;
                $user->save();            
            }

            $combined_order = CombinedOrder::where('id', $decoded_response['combined_order_id'])->first();

            foreach ($combined_order->orders as $key => $order) {
                calculateCommissionAffilationClubPoint($order);
            }
            
            return $response;

        } else {
            return response()->json([
                'result' => false,
                'combined_order_id' => 0,
                'message' => translate('Insufficient wallet balance')
            ]);
        }
    }

    public function offline_recharge(Request $request)
    {
        $wallet = new Wallet;
        $wallet->user_id = auth()->user()->id;
        $wallet->amount = $request->amount;
        $wallet->payment_method = $request->payment_option;
        $wallet->payment_details = $request->trx_id;
        $wallet->approval = 0;
        $wallet->offline_payment = 1;
        $wallet->reciept = $request->photo;
        $wallet->type = 'in'; // Money flowing into the wallet
        $wallet->save();
        return response()->json([
            'result' => true,
            'message' => translate('Offline Recharge has been done. Please wait for response.')
        ]);
    }



    public function wallet_info()
    {

    }

}
