<?php


namespace App\Http\Controllers\Api\V2;


use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;

class CheckoutController
{
    public function apply_coupon_code(Request $request)
    {
        $coupon = Coupon::where('code', $request->coupon_code)->first();
        if ($coupon == null) {
            return response()->json([
                'result' => false,
                'message' => translate('Invalid coupon code!')
            ]);
        }

        $user_id        = auth()->user()->id;
        $temp_user_id   = $request->temp_user_id;
        
        $cart_items     = ($user_id != null) ?
                            Cart::where('user_id', $user_id)->where('owner_id', $coupon->user_id)->active()->get():
                            Cart::where('temp_user_id', $temp_user_id)->where('owner_id', $coupon->user_id)->active()->get();

        $coupon_discount = 0;
        if ($cart_items->isEmpty()) {
            return response()->json([
                'result' => false,
                'message' => translate('This coupon is not applicable to your cart products!')
            ]);
        }

        $in_range = strtotime(date('d-m-Y')) >= $coupon->start_date && strtotime(date('d-m-Y')) <= $coupon->end_date;

        if (!$in_range) {
            return response()->json([
                'result' => false,
                'message' => translate('Coupon expired!')
            ]);
        }

        // check if user already used this coupon
        if($user_id != null){
            $is_used = CouponUsage::where('user_id', $user_id)->where('coupon_id', $coupon->id)->first() != null;
            if ($is_used) {
                return response()->json([
                    'result' => false,
                    'message' => translate('You already used this coupon!')
                ]);
            }
        }
        
        $coupon_details = json_decode($coupon->details);


        if ($coupon->type == 'cart_base') {
            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            foreach ($cart_items as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $subtotal += cart_product_price($cartItem, $product, false, false) * $cartItem['quantity'];
                $tax += cart_product_tax($cartItem, $product,false) * $cartItem['quantity'];
                $shipping += $cartItem['shipping'] * $cartItem['quantity'];
            }
            $sum = $subtotal + $tax + $shipping;

            if ($sum >= $coupon_details->min_buy) {
                if ($coupon->discount_type == 'percent') {
                    $coupon_discount = ($sum * $coupon->discount) / 100;
                    if ($coupon_discount > $coupon_details->max_discount) {
                        $coupon_discount = $coupon_details->max_discount;
                    }
                } elseif ($coupon->discount_type == 'amount') {
                    $coupon_discount = $coupon->discount;
                }
            }
        } elseif ($coupon->type == 'product_base') {

            foreach ($cart_items as $key => $cartItem) {
                $product = Product::find($cartItem['product_id']);
                foreach ($coupon_details as $key => $coupon_detail) {
                    if ($coupon_detail->product_id == $cartItem['product_id']) {
                        if ($coupon->discount_type == 'percent') {
                            $coupon_discount += cart_product_price($cartItem, $product, false, false) * $coupon->discount / 100;
                        } elseif ($coupon->discount_type == 'amount') {
                            $coupon_discount += $coupon->discount;
                        }
                    }
                }
            }

        }
        $coupon_discount = Coupon::where('code', $request->coupon_code)->first()->discount;
        if($coupon_discount>0){
            
            $cart_query = $user_id != null ? Cart::where('user_id', $user_id) : Cart::where('temp_user_id', $temp_user_id);
            $cart_query->where('owner_id', $coupon->user_id)->active()->update([
                'discount' => $coupon_discount / count($cart_items),
                'coupon_code' => $request->coupon_code,
                'coupon_applied' => 1
            ]);
            

            return response()->json([
                'result' => true,
                'message' => translate('Coupon Applied')
            ]);
        }else{
            return response()->json([
                'result' => false,
                'message' => translate('This coupon is not applicable to your cart products!')
            ]);
        }

    }


    public function remove_coupon_code(Request $request)
    {
        $user_id        = auth()->user()->id;
        $temp_user_id   = $request->temp_user_id;
        $cart_query = $user_id != null ? Cart::where('user_id', $user_id) : Cart::where('temp_user_id', $temp_user_id);
        $cart_query->update([
            'discount' => 0.00,
            'coupon_code' => "",
            'coupon_applied' => 0
        ]);

        return response()->json([
            'result' => true,
            'message' => translate('Coupon Removed')
        ]);
    }
}
