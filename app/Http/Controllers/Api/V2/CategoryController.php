<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\V2\BrandCollection;
use App\Http\Resources\V2\CategoryCollection;
use App\Http\Resources\V2\ProductCollection;
use App\Http\Resources\V2\ProductDetailCollection;
use App\Http\Resources\V2\Seller\ProductResource;
use App\Http\Resources\V2\SliderCollection;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Models\User;
use Cache;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Mpdf\Tag\B;
use Request;

class CategoryController extends Controller
{

    public function index(Request $request,$parent_id = 0)
    {

        if (request()->has('parent_id') && request()->parent_id) {
            $category = Category::where('slug', request()->parent_id)->first();
            $parent_id = $category->id;
        }

        // return Cache::remember("app.categories-$parent_id", 86400, function () use ($parent_id) {
            return new CategoryCollection(Category::where('parent_id', $parent_id)->whereDigital(0)->paginate(request()->per_page??10));
        // });
    }

    public function info($slug)
    {
        return new CategoryCollection(Category::where('slug', $slug)->get());
    }

    public function featured()
    {
        return Cache::remember('app.featured_categories', 86400, function () {
            return new CategoryCollection(Category::where('featured', 1)->get());
        });
    }

       public function bannerOne()
    {
        $getImages = get_setting('home_banner1_images', null, request()->header('App-Language'));
        $images = $getImages != null ? json_decode($getImages, true) : [];
        $getLinks = get_setting('home_banner1_links', null, request()->header('App-Language'));
        $links = ($getImages != null && $getLinks != null) ? json_decode($getLinks, true) : [];

        $banners = [];
        for ($i = 0; $i < count($images); $i++) {
            $banners[$i] = ['link' => $links[$i], "image" => $images[$i]];
        }
        return new SliderCollection($banners);
    }

    public function bannerTwo()
    {
        $getImages = get_setting('home_banner2_images', null, request()->header('App-Language'));
        $images = $getImages != null ? json_decode($getImages, true) : [];
        $getLinks = get_setting('home_banner2_links', null, request()->header('App-Language'));
        $links = ($getImages != null && $getLinks != null) ? json_decode($getLinks, true) : [];

        $banners = [];
        for ($i = 0; $i < count($images); $i++) {
            $banners[$i] = ['link' => $links[$i], "image" => $images[$i]];
        }
        return new SliderCollection($banners);
    }

    public function home(Request $request)
    {
        // return Cache::remember('app.home_categories', 86400, function () {
        //     return new CategoryCollection(Category::whereIn('id', json_decode(get_setting('home_categories')))->get());
        // });
        // $token = request()->bearerToken();

        // if ($token) {
        //     $accessToken = PersonalAccessToken::findToken($token);
        //     if ($accessToken) {
        //         Auth::login($accessToken->tokenable);
        //     }
        // }

        $userId = auth()->id();
    
        $featured_categories = Category::paginate(10);
        $latest_products   = Product::latest()->paginate(10);
        $best_selling_products =Product::orderBy('num_of_sale', 'desc')->physical()->paginate(10);
       

        $brands =Brand::paginate(10);


        $slider1 = Slider::where('type', 'slider1')->get();
        $slider2 = Slider::where('type', 'slider2')->get();

       // Map photo paths
    //   $slider1 = Slider::where('type', 'slider1')->pluck('photo')->map(function ($photo) {
    //         return asset($photo);
    //     })->toArray();

    //     $slider2 = Slider::where('type', 'slider2')->pluck('photo')->map(function ($photo) {
    //         return asset($photo);
    //     })->toArray();

        return response()->json([
        'categories' => new CategoryCollection($featured_categories),
        'latest_products' => new ProductDetailCollection($latest_products),
        'best_selling_products' => new ProductDetailCollection($best_selling_products),
        'brands' => new BrandCollection($brands),

        'slider1' => new SliderCollection($slider1),
        'slider2' => new SliderCollection($slider2),
    ]);



    }

    public function top()
    {
        return Cache::remember('app.top_categories', 86400, function () {
            return new CategoryCollection(Category::whereIn('id', json_decode(get_setting('home_categories')))->limit(20)->get());
        });
    }
}
