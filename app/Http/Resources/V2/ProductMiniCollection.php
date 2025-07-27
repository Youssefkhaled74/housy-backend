<?php

namespace App\Http\Resources\V2;

use App\Models\Cart;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductMiniCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                $wholesale_product =
                    ($data->wholesale_product == 1) ? true : false;
                return [
                    'id' => $data->id,
                    'name' => $data->getTranslation('name'),
                    'description' => $data->getTranslation('description'),
                    'slug' => $data->slug,
                    'photos' => collect(explode(',', $data->photos))->map(function ($id) {
                        return uploaded_asset($id);
                    })->filter()->values(),
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'has_discount' => home_base_price($data, false) != home_discounted_base_price($data, false),
                    'discount' => "-" . discount_in_percentage($data) . "%",
                    'stroked_price' => home_base_price($data),
                    'main_price' => home_discounted_base_price($data),
					'unit_price'=>$data->unit_price,
                    'rating' => (float) $data->rating,
                    'is_in_wishlist' => auth()->check() && Wishlist::where('product_id', $data->id)
                    ->where('user_id', auth()->id())->exists(),

                    'is_in_cart' => auth()->check() && Cart::where('product_id', $data->id)
                                        ->where('user_id', auth()->id())->exists(),
                    //'sales' => (int) $data->num_of_sale,
                    'rating_count' => (int)Review::where(['product_id' => $data->id])->count(),
                    'shop_slug' => $data->added_by == 'admin' ? '' : $data->user?->shop->slug,
                    'shop_name' => $data->added_by == 'admin' ? translate('In House Product') : $data->user?->shop->name,
                    'shop_logo' => $data->added_by == 'admin' ? uploaded_asset(get_setting('header_logo')) : uploaded_asset($data->user?->shop->logo) ?? "",
                    'shop_phone' => $data->added_by == 'admin' ?'' :
                    ($data->user?->shop->seller_package? $data->user->shop->phone: ''),
                    'shop_address' => $data->added_by == 'admin' ? '' :
                    ($data->user?->shop->seller_package? $data->user->shop->address: ''),
                    'lat' => $data->added_by == 'admin' ? '' :
                    ($data->user?->shop->seller_package? $data->user->shop->lat: ''),
                    'long' => $data->added_by == 'admin' ? '' :
                    ($data->user?->shop->seller_package? $data->user->shop->long: ''),
                  
                    //'is_wholesale' => $wholesale_product,
                    // 'links' => [
                    //     'details' => route('products.show', $data->id),
                    // ]
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
