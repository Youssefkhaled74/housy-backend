<?php

namespace App\Http\Resources\V2;

use App\Models\Cart;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'id' => $data->id,
                    'slug' => $data->slug,
                    'name' => $data->getTranslation('name'),
                    'description' => $data->getTranslation('description'),
                    'photos' => collect(explode(',', $data->photos))->map(function ($id) {
                        return uploaded_asset($id);
                    })->filter()->values(),                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'base_price' => (float) home_base_price($data, false),
                    'base_discounted_price' => (float) home_discounted_base_price($data, false),
                    'discount' => (float) $data->discount,
                    'discount_type' => $data->discount_type,
                    'rating' => (float) $data->rating,
                    'rating_count' => (int)Review::where(['product_id' => $data->id])->count(),
                    'is_in_wishlist' => auth()->check() && Wishlist::where('product_id', $data->id)
                                ->where('user_id', auth()->id())->exists(),

                    'is_in_cart' => auth()->check() && Cart::where('product_id', $data->id)
                                        ->where('user_id', auth()->id())->exists(),
                    //'todays_deal' => (int) $data->todays_deal,
                    //'featured' => (int) $data->featured,
                    //'unit' => $data->unit,
                   // 'sales' => (int) $data->num_of_sale,
                    // 'links' => [
                    //     'details' => route('products.show', $data->id),
                    //     'reviews' => route('api.reviews.index', $data->id),
                    //     'related' => route('products.related', $data->id),
                    //     'top_from_seller' => route('products.topFromSeller', $data->id)
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
