<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\Wallet;
use DB;
use \App\Utility\NotificationUtility;
use App\Models\CombinedOrder;
use App\Http\Controllers\AffiliateController;
class OrderController extends Controller
{


    public function store(Request $request, $set_paid = false)
    {
        if (get_setting('minimum_order_amount_check') == 1) {
            $subtotal = 0;
            foreach (Cart::where('user_id', auth()->user()->id)->active()->get() as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
            }
            if ($subtotal < get_setting('minimum_order_amount')) {
                return $this->failed(translate("You order amount is less then the minimum order amount"));
            }
        }

        $cartItems = Cart::where('user_id', auth()->user()->id)->active()->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'combined_order_id' => 0,
                'result' => false,
                'message' => translate('Cart is Empty')
            ]);
        }

        $user = User::find(auth()->user()->id);

        $shippingAddress = [
            'name'        => $request->firstname . ' ' . $request->lastname,
            'email'       => $request->email,
            'address'     => $request->address,
            'country'     => $request->country,
            'state'       => $request->state ?? null,
            'city'        => $request->city,
            'postal_code' => $request->postal_code ?? null,
            'phone'       => $request->phone,
        ];
        
        // Create combined order
        $combined_order = new CombinedOrder;
        $combined_order->user_id = $user->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();

        // Group cart items by seller
        $seller_products = array();
        foreach ($cartItems as $cartItem) {
            $product_ids = array();
            $product = Product::find($cartItem['product_id']);
            if (isset($seller_products[$product->user_id])) {
                $product_ids = $seller_products[$product->user_id];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product->user_id] = $product_ids;
        }

        $grand_total = 0;
        $coupon_discount_total = 0;

        // Create orders for each seller's products
        foreach ($seller_products as $seller_product) {
            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = $user->id;
            $order->shipping_address = $combined_order->shipping_address;

            $order->order_from = 'app';
            $order->payment_type = $request->payment_type;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = date('Ymd-His') . rand(10, 99);
            $order->date = strtotime('now');
            if ($set_paid) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'unpaid';
            }

            $order->save();

            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            $coupon_discount = 0;

            //Order Details Storing
            foreach ($seller_product as $cartItem) {
                $product = Product::find($cartItem['product_id']);

                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $tax += cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                $coupon_discount += $cartItem['discount'];

                $product_variation = $cartItem['variation'];

                $product_stock = $product->stocks->where('variant', $product_variation)->first();
                if ($product->digital != 1 && $cartItem['quantity'] > $product_stock?->qty) {
                    $order->delete();
                    $combined_order->delete();
                    return response()->json([
                        'combined_order_id' => 0,
                        'result' => false,
                        'message' => translate('The requested quantity is not available for ') . $product->name
                    ]);
                } elseif ($product->digital != 1) {
                    $product_stock->qty -= $cartItem['quantity'];
                    $product_stock->save();
                }

                $order_detail = new OrderDetail;
                $order_detail->order_id = $order->id;
                $order_detail->seller_id = $product->user_id;
                $order_detail->product_id = $product->id;
                $order_detail->variation = $product_variation;
                $order_detail->price = cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $order_detail->tax = cart_product_tax($cartItem, $product, false) * $cartItem['quantity'];
                $order_detail->shipping_type = $cartItem['shipping_type'];
                $order_detail->product_referral_code = $cartItem['product_referral_code'];
                $order_detail->shipping_cost = $cartItem['shipping_cost'];

                $shipping += $order_detail->shipping_cost;

                //End of storing shipping cost
                if (addon_is_activated('club_point')) {
                    $order_detail->earn_point = $product->earn_point;
                }

                $order_detail->quantity = $cartItem['quantity'];
                $order_detail->save();

                $product->num_of_sale = $product->num_of_sale + $cartItem['quantity'];
                $product->save();

                $order->seller_id = $product->user_id;

                $order->shipping_type = $cartItem['shipping_type'];
                if ($cartItem['shipping_type'] == 'pickup_point') {
                    $order->pickup_point_id = $cartItem['pickup_point'];
                }
                if ($cartItem['shipping_type'] == 'carrier') {
                    $order->carrier_id = $cartItem['carrier_id'];
                }

                if ($product->added_by == 'seller' && $product->user->seller != null) {
                    $seller = $product->user->seller;
                    $seller->num_of_sale += $cartItem['quantity'];
                    $seller->save();
                }

                // Store referral code for affiliate processing
            if (addon_is_activated('affiliate_system')) {
                    if ($order_detail->product_referral_code) {
                        $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();

                        $affiliateController = new AffiliateController;
                        $affiliateController->processAffiliateStats($referred_by_user->id, 0, $order_detail->quantity, 0, 0);
                    }
                }
            }

            $order->grand_total = $subtotal + $tax + $shipping;

            // Apply coupon discount if coupon code exists
            if ($request->coupon_code || ($seller_product[0]->coupon_code != null)) {
                $coupon_code = $request->coupon_code ?? $seller_product[0]->coupon_code;
                $coupon = Coupon::where('code', $coupon_code)->first();
                
                if ($coupon) {
                    $order->coupon_discount = $coupon_discount;
                    $order->grand_total -= $coupon_discount;
                    $coupon_discount_total += $coupon_discount;

                    $coupon_usage = new CouponUsage;
                    $coupon_usage->user_id = $user->id;
                    $coupon_usage->coupon_id = $coupon->id;
                    $coupon_usage->save();
                }
            }

            $grand_total += $order->grand_total;

            if (strpos($request->payment_type, "manual_payment_") !== false) {
                $order->manual_payment = 1;
                $order->save();
            }

            $order->save();
        }

        $combined_order->grand_total = $grand_total;
        $combined_order->save();

        // Handle wallet payment
        $wallet_amount_used = 0;
        
        if ($request->payment_type == 'wallet' || $request->payment_type == 'wallet_and_credit') {
            $user_balance = $user->balance;
            
            if ($user_balance >= $grand_total && $request->payment_type == 'wallet') {
                // Full wallet payment
                $user->balance -= $grand_total;
                $user->save();
                
                // Record wallet transaction
                $wallet = new Wallet;
                $wallet->user_id = $user->id;
                $wallet->amount = $grand_total;
                $wallet->payment_method = 'wallet';
                $wallet->payment_details = 'Order ID: ' . $combined_order->id;
                $wallet->type = 'out'; // debit
                $wallet->save();
                
                // Update order payment status
                foreach ($combined_order->orders as $order) {
                    $order->payment_status = 'paid';
                    $order->payment_type = 'wallet';
                    $order->save();
                }
                
                $wallet_amount_used = $grand_total;
                $set_paid = true;
            } 
            elseif ($request->payment_type == 'wallet_and_credit' && $user_balance > 0) {
                // Partial wallet payment + COD
                $wallet_amount_used = $user_balance;
                $user->balance = 0;
                $user->save();
                
                // Record wallet transaction
                $wallet = new Wallet;
                $wallet->user_id = $user->id;
                $wallet->amount = $wallet_amount_used;
                $wallet->payment_method = 'wallet';
                $wallet->payment_details = 'Order ID: ' . $combined_order->id;
                $wallet->type = 'out'; // debit
                $wallet->save();
                
                // Update order payment type
                foreach ($combined_order->orders as $order) {
                    $order->payment_type = 'wallet_and_credit';
                    $order->save();
                }
            }
        }

        Cart::where('user_id', auth()->user()->id)->active()->delete();

        // Send notifications for appropriate payment types
        if (
            $request->payment_type == 'cash_on_delivery' ||
            $request->payment_type == 'wallet' ||
            $request->payment_type == 'wallet_and_credit' ||
            strpos($request->payment_type, "manual_payment_") !== false ||
            $set_paid
        ) {
            foreach ($combined_order->orders as $order) {
                NotificationUtility::sendOrderPlacedNotification($order);
            }
        }

        return response()->json([
            'combined_order_id' => $combined_order->id,
            'result' => true,
            'message' => translate('Your order has been placed successfully'),
            'total_amount' => $grand_total,
            'wallet_amount_used' => $wallet_amount_used,
            'remaining_amount' => $grand_total - $wallet_amount_used,
            'coupon_discount' => $coupon_discount_total,
            'payment_status' => $set_paid ? 'paid' : 'unpaid',
            'payment_method' => $request->payment_type
        ]);
    }

    public function order_cancel($id)
    {
        $order = Order::where('id', $id)->where('user_id', auth()->user()->id)->first();
        if ($order && ($order->delivery_status == 'pending' && $order->payment_status == 'unpaid')) {
            $order->delivery_status = 'cancelled';
            $order->save();

            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->delivery_status = 'cancelled';
                $orderDetail->save();
                product_restock($orderDetail);
            }

            return $this->success(translate('Order has been canceled successfully'));
        } else {
            return  $this->failed(translate('Something went wrong'));
        }
    }
}
