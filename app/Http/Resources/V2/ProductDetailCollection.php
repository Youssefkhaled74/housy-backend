<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Models\Review;
use App\Models\Attribute;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;
use Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ProductDetailCollection extends ResourceCollection
{


    protected $counter = 2; // default

    public function setCounter($counter)
    {
    $this->counter = $counter;
    return $this;
    }
    public function toArray($request)
    {


        $token = request()->bearerToken();

            if (!auth()->check()) {
                $token = request()->bearerToken();

                if ($token) {
                    $accessToken = PersonalAccessToken::findToken($token);
                    if ($accessToken) {
                        Auth::setUser($accessToken->tokenable);
                    }
                }
            }

            $items = $this->collection->map(function ($data) {


                // $precision = 2;
                // $calculable_price = home_discounted_base_price($data, false);
                // $calculable_price = number_format($calculable_price, $precision, '.', '');
                // $calculable_price = floatval($calculable_price);

            $photo_paths = get_images_path($data->photos);
                $photos = [];

                if (!empty($photo_paths)) {
                    foreach ($photo_paths as $path) {
                        if ($path != "") {
                            $photos[] = $path;
                        }
                    }
                }

                foreach ($data->stocks as $stockItem) {
                    if (!empty($stockItem->image)) {
                        $photos[] = uploaded_asset($stockItem->image);
                    }
                }

                // $brand = [
                //     'id' => 0,
                //     'name' => '',
                //     'slug' => '',
                //     'logo' => '',
                // ];

                // if ($data->brand) {
                //     $brand = [
                //         'id' => $data->brand->id,
                //         'slug' => $data->brand->slug,
                //         'name' => $data->brand->getTranslation('name'),
                //         'logo' => uploaded_asset($data->brand->logo),
                //     ];
                // }

                // $whole_sale = [];
                // if (addon_is_activated('wholesale')) {
                //     $whole_sale = ProductWholesaleResource::collection($data->stocks->first()->wholesalePrices);
                // }
                return [
                    'id' => (int)$data->id,
                    'name' =>translate($data->name), //$data->getTranslation('name'),
                    'description' =>translate($data->description),// $data->getTranslation('description'),
                    'photos' => $photos,
                    'thumbnail_image' => uploaded_asset($data->thumbnail_img),
                    'tags' => explode(',', $data->tags),
                    'colors' => json_decode($data->colors) ?? [],
                    // 'has_discount' => home_base_price($data, false) != home_discounted_base_price($data, false),
                    // 'discount' => "-" . discount_in_percentage($data) . "%",
                    // 'price_with_tax' => home_base_price($data), //bl taxes bs
                    // 'main_price' => home_discounted_base_price($data),  //bl discount
                    'unit_price'=>$data->unit_price,
                    'discount'=>$data->discount,
                    'tax'=>$data->tax,
                    // 'calculable_price' => $calculable_price,
                    'currency_symbol' => currency_symbol(),
                    'unit' => $data->unit ?? "",
                    'rating' => (float)$data->rating,
                    'rating_count' => (int)Review::where(['product_id' => $data->id])->count(),
                    'added_by' => $data->added_by,
                    'seller_id' => $data->user?->id,
                    "seller_package_name" => $data->user?->shop?->seller_package,
                    "amount"=>( $data->user?->shop?->seller_package?->amount > 0) ? single_price( $data->user?->shop->seller_package?->amount) : translate('Free'),


                    'is_in_wishlist' => auth()->check() && Wishlist::where('product_id', $data->id)
                        ->where('user_id', auth()->id())->first()?->exists(),

                    'is_in_cart' => auth()->check() && Cart::where('product_id', $data->id)
                                            ->where('user_id', auth()->id())->first()?->exists(),

                    'cart_quantity'=>auth()->check() ?Cart::where('product_id', $data->id)
                                            ->where('user_id', auth()->id())->first()?->quantity:0,
                    'shop' => $data->added_by === 'admin'
                    ? [
                        'id' => 0,
                        'name' => translate('In House Product'),
                        'slug' => '',
                        'logo' => uploaded_asset(get_setting('header_logo')),
                        'phone' => '',
                        'address' => '',
                        'lat' => '',
                        'long' => '',
                        'rating' =>'',
                        'products'=> '',
                        'orders'=> '',

                        ]
                        : new ShopResource($data->user?->shop),

                    'earn_point' => (float)$data->earn_point,
                    'reviews' => new ReviewCollection(Review::where('product_id', $data->id)->where('status', 1)->latest()->take(3)->get()),
                    // 'related_products' => new ProductDetailCollection( $relatedProducts),

                            // 'added_by' => $data->added_by,
                            // 'shop_id' => $data->added_by == 'admin' ? 0 : $data->user?->shop->id,
                            // 'shop_slug' => $data->added_by == 'admin' ? '' : $data->user?->shop->slug,
                            // 'shop_name' => $data->added_by == 'admin' ? translate('In House Product') : $data->user?->shop->name,
                            // 'shop_logo' => $data->added_by == 'admin' ? uploaded_asset(get_setting('header_logo')) : uploaded_asset($data->user?->shop->logo) ?? "",
                            // 'shop_phone' => $data->added_by == 'admin' ? '' : ($data->user?->shop->seller_package ? $data->user?->shop->phone : ''),
                            // 'shop_address' => $data->added_by == 'admin' ? '' : ($data->user?->shop->seller_package ? $data->user?->shop->address : ''),

                            // 'lat' => $data->added_by == 'admin' ? '' : ($data->user?->shop->seller_package ? $data->user?->shop->lat : ''),
                    // 'long' => $data->added_by == 'admin' ? '' : ($data->user?->shop->seller_package ? $data->user?->shop->long : ''),
                        //'current_stock' => (int)$data->stocks->first()->qty,
                        // 'price_high_low' => (float)explode('-', home_discounted_base_price($data, false))[0] == (float)explode('-', home_discounted_price($data, false))[1] ? format_price((float)explode('-', home_discounted_price($data, false))[0]) : "From " . format_price((float)explode('-', home_discounted_price($data, false))[0]) . " to " . format_price((float)explode('-', home_discounted_price($data, false))[1]),
                        // 'choice_options' => $this->convertToChoiceOptions(json_decode($data->choice_options)),
                        //'earn_point' => (float)$data->earn_point,
                        // 'downloads' => $data->pdf ? uploaded_asset($data->pdf) : null,
                        // 'video_link' => $data->video_link != null ?  $data->video_link : "",
                        //'brand' => $brand,
                        // 'link' => route('product', $data->slug),
                            //'wholesale' => $whole_sale,
                            //'est_shipping_time' => (int)$data->est_shipping_days,
                ];
            });
            if ($this->counter === 1) {
                    return $items->first();
                }

                return [
                    'data' => $items,
                ];
    }



    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }

    protected function convertToChoiceOptions($data)
    {
        $result = array();
        if ($data) {
            foreach ($data as $key => $choice) {
                $item['name'] = $choice->attribute_id;
                $item['title'] = Attribute::find($choice->attribute_id)->getTranslation('name');
                $item['options'] = $choice->values;
                array_push($result, $item);
            }
        }
        return $result;
    }

    protected function convertPhotos($data)
    {
        $result = array();
        foreach ($data as $key => $item) {
            array_push($result, uploaded_asset($item));
        }
        return $result;
    }
}
