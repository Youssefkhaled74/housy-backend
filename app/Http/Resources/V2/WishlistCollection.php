<?php

namespace App\Http\Resources\V2;

use App\Models\Cart;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WishlistCollection extends ResourceCollection
{
     public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $product = $data->product; // get the product model
                return [
                    'id' => (int) $data->id, // wishlist id
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->getTranslation('name'),
                        'description' => $product->getTranslation('description'),
                        'slug' => $product->slug,
                        'photos' => collect(explode(',', $product->photos))->map(fn($id) => uploaded_asset($id))->filter()->values(),
                        'thumbnail_image' => uploaded_asset($product->thumbnail_img),
                        'has_discount' => home_base_price($product, false) != home_discounted_base_price($product, false),
                        'discount' => "-" . discount_in_percentage($product) . "%",
                        'stroked_price' => home_base_price($product),
                        'main_price' => home_discounted_base_price($product),
                        'rating' => (float) $product->rating,
                        'rating_count' => (int) Review::where(['product_id' => $product->id])->count(),
                        'is_in_wishlist' => auth()->check() && Wishlist::where('product_id', $product->id)->where('user_id', auth()->id())->exists(),
                        'is_in_cart' => auth()->check() && Cart::where('product_id', $product->id)->where('user_id', auth()->id())->exists(),
                        'shop_slug' => $product->added_by == 'admin' ? '' : optional($product->user->shop)->slug,
                        'shop_name' => $product->added_by == 'admin' ? translate('In House Product') : optional($product->user->shop)->name,
                        'shop_logo' => $product->added_by == 'admin' ? uploaded_asset(get_setting('header_logo')) : uploaded_asset(optional($product->user->shop)->logo),
                        'shop_phone' => $product->added_by == 'admin' ? '' : (optional($product->user->shop->seller_package) ? $product->user->shop->phone : ''),
                        'shop_address' => $product->added_by == 'admin' ? '' : (optional($product->user->shop->seller_package) ? $product->user->shop->address : ''),
                        'lat' => $product->added_by == 'admin' ? '' : (optional($product->user->shop->seller_package) ? $product->user->shop->lat : ''),
                        'long' => $product->added_by == 'admin' ? '' : (optional($product->user->shop->seller_package) ? $product->user->shop->long : ''),
                    ]
                ];
            })
        ];
    }


    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
