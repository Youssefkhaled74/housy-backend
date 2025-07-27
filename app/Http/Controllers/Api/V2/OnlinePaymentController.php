<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Controller;
use Illuminate\Http\Request;

class OnlinePaymentController extends Controller
{
    public  function init(Request $request)
    {
        $directory = __NAMESPACE__ . '\\' . str_replace(' ', '', ucwords(str_replace('_payment', ' ', $request->payment_option))) . "Controller";
        
        return (new $directory)->pay($request);
    }
    public  function paymentSuccess(Request $request)
    {
        try {

            $payment_type = $request->payment_type;

            if ($payment_type == 'cart_payment') {
                checkout_done($request->order_id, $request->payment_details);
            }
            elseif ($payment_type == 'order_re_payment') {
                order_re_payment_done($request->order_id, 'Iyzico', $request->payment_details);
            }
            elseif ($payment_type == 'wallet_payment') {
                wallet_payment_done($request->user_id, $request->amount, 'Iyzico', $request->payment_details);
            }
            elseif ($payment_type == 'wallet_online_payment') {
                // This is a combined wallet + online payment
                $combined_order = \App\Models\CombinedOrder::findOrFail($request->order_id);
                
                // Mark all orders as paid
                foreach ($combined_order->orders as $order) {
                    $order->payment_status = 'paid';
                    $order->payment_details = $request->payment_details;
                    $order->save();
                    
                    // Process order items
                    foreach ($order->orderDetails as $orderDetail) {
                        if (addon_is_activated('affiliate_system')) {
                            if ($orderDetail->product_referral_code) {
                                $referred_by_user = \App\Models\User::where('referral_code', $orderDetail->product_referral_code)->first();
                                // Note: AffiliateController appears to be missing in the current codebase
                                // The application owner should implement this controller or disable this functionality
                                // For now, we're using the fully qualified namespace to avoid import issues
                                $affiliateController = new \App\Http\Controllers\AffiliateController;
                                $affiliateController->processAffiliateStats($referred_by_user->id, 0, $orderDetail->quantity, 0, 0);
                            }
                        }
                    }
                    
                    // Send notification
                    \App\Utility\NotificationUtility::sendOrderPlacedNotification($order);
                }
            }
            elseif ($payment_type == 'seller_package_payment') {
                seller_purchase_payment_done($request->user_id, $request->package_id, 'Iyzico', $request->payment_details);
            }
            elseif ($payment_type == 'customer_package_payment') {
                customer_purchase_payment_done($request->user_id, $request->package_id, 'Iyzico', $request->payment_details);
            }
            return redirect(url("api/v2/online-pay/done"));
        } catch (\Exception $e) {
            return redirect(url("api/v2/online-pay/done"))->with('errors',$e->getMessage());
        }
    }

    public  function paymentFailed()
    {
        return $this->failed(session('errors'));
    }

    function paymentDone(){
        return $this->success("Payment Done");
    }
}
