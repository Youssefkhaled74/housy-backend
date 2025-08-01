<?php

namespace App\Http\Controllers\Api\V2;

use Cache;
use App\Models\Shop;
use App\Models\Color;
use App\Models\Product;
use App\Models\FlashDeal;
use Illuminate\Http\Request;
use App\Utility\SearchUtility;
use App\Utility\CategoryUtility;
use App\Http\Resources\V2\FlashDealCollection;
use App\Http\Resources\V2\LastViewedProductCollection;
use App\Http\Resources\V2\ProductCollection;
use App\Http\Resources\V2\ProductMiniCollection;
use App\Http\Resources\V2\ProductDetailCollection;
use App\Models\Brand;
use App\Models\Category;
use App\Http\Resources\V2\Seller\BrandCollection;
use App\Models\Review;

class ProductController extends Controller
{
    public function index(Request $request)
    {

       return new ProductDetailCollection(Product::latest()->paginate($request->perPage??10));
    }
    public function show()
    {
        return new ProductMiniCollection(Product::latest()->paginate(10));
    }

   public function findById(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $categoryIds = $product->categories->pluck('id');

        $relatedProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $product->id) 
            ->distinct()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'product_details' => (new ProductDetailCollection(collect([$product])))->setCounter(1),
            'related_products' => new ProductDetailCollection($relatedProducts),
        ]);
    }


    public function product_details(Request $request,$slug, $user_id)
    {
        $product = Product::where('slug', $slug)->get();
        if(get_setting('last_viewed_product_activation') == 1 && $user_id != null){
            lastViewedProducts($product[0]->id, $user_id);
        }
        return new ProductDetailCollection($product->paginate($request->per_page??10));
    }

    public function getPrice(Request $request)
    {
        $product = Product::where("slug", $request->slug)->first();
        $str = '';
        $tax = 0;
        $quantity = 1;



        if ($request->has('quantity') && $request->quantity != null) {
            $quantity = $request->quantity;
        }

        if ($request->has('color') && $request->color != null) {
            $str = Color::where('code', '#' . $request->color)->first()->name;
        }

        $var_str = str_replace(',', '-', $request->variants);
        $var_str = str_replace(' ', '', $var_str);

        if ($var_str != "") {
            $temp_str = $str == "" ? $var_str : '-' . $var_str;
            $str .= $temp_str;
        }

        $product_stock = $product->stocks->where('variant', $str)->first();
        $price = $product_stock->price;


        if ($product->wholesale_product) {
            $wholesalePrice = $product_stock->wholesalePrices->where('min_qty', '<=', $quantity)->where('max_qty', '>=', $quantity)->first();
            if ($wholesalePrice) {
                $price = $wholesalePrice->price;
            }
        }

        $stock_qty = $product_stock->qty;
        $stock_txt = $product_stock->qty;
        $max_limit = $product_stock->qty;

        if ($stock_qty >= 1 && $product->min_qty <= $stock_qty) {
            $in_stock = 1;
        } else {
            $in_stock = 0;
        }

        //Product Stock Visibility
        if ($product->stock_visibility_state == 'text') {
            if ($stock_qty >= 1 && $product->min_qty < $stock_qty) {
                $stock_txt = translate('In Stock');
            } else {
                $stock_txt = translate('Out Of Stock');
            }
        }

        //discount calculation
        $discount_applicable = false;

        if ($product->discount_start_date == null) {
            $discount_applicable = true;
        } elseif (
            strtotime(date('d-m-Y H:i:s')) >= $product->discount_start_date &&
            strtotime(date('d-m-Y H:i:s')) <= $product->discount_end_date
        ) {
            $discount_applicable = true;
        }

        if ($discount_applicable) {
            if ($product->discount_type == 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type == 'amount') {
                $price -= $product->discount;
            }
        }

        // taxes
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        $price += $tax;

        return response()->json(

            [
                'result' => true,
                'data' => [
                    'price' => single_price($price * $quantity),
                    'stock' => $stock_qty,
                    'stock_txt' => $stock_txt,
                    'digital' => $product->digital,
                    'variant' => $str,
                    'variation' => $str,
                    'max_limit' => $max_limit,
                    'in_stock' => $in_stock,
                    'image' => $product_stock->image == null ? "" : uploaded_asset($product_stock->image)
                ]

            ]
        );
    }

    public function seller($id, Request $request)
    {
        $shop = Shop::findOrFail($id);
        $products = Product::where('added_by', 'seller')->where('user_id', $shop->user_id);
        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }
        $products->where('published', 1);
        return new ProductMiniCollection($products->latest()->paginate(10));
    }

    public function categoryProducts($id, Request $request)
    {
        $category = Category::where('id', $id)->first();
        $products = $category->products();

        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }

        return new ProductDetailCollection(filter_products($products)->latest()->paginate($request->per_page??10));
    }

    public function brand($id, Request $request)
    {
        $brand = Brand::where('id', $id)->first();
        $products = Product::where('brand_id', $brand->id)->physical();
        if ($request->name != "" || $request->name != null) {
            $products = $products->where('name', 'like', '%' . $request->name . '%');
        }
        $filteredProducts = filter_products($products);

        return response()->json([
            "products"=> new ProductDetailCollection($filteredProducts->latest()->paginate($request->per_page??10)),
            "num_products" => $filteredProducts->count(),
            "num_reviews" =>Review::whereIn('product_id', $filteredProducts->pluck('id'))->count(),
            "num_orders" => Product::whereIn('id', $filteredProducts->pluck('id'))->sum('num_of_sale'),
        ]);
    }

    public function getBrands()
    {
        $brands = Brand::all();

        return BrandCollection::collection($brands);
    }

    public function todaysDeal()
    {
        $products = Product::where('todays_deal', 1)->physical();
        return new ProductMiniCollection(filter_products($products)->limit(20)->latest()->get());
    }

    public function flashDeal()
    {
        return Cache::remember('app.flash_deals', 86400, function () {
            $flash_deals = FlashDeal::where('status', 1)->where('featured', 1)->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')))->get();
            return new FlashDealCollection($flash_deals);
        });
    }

    public function featured()
    {
        $products = Product::where('featured', 1)->physical();
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(10));
    }

    public function inhouse()
    {
        $products = Product::where('added_by', 'admin');
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(12));
    }

    public function digital()
    {
        $products = Product::digital();
        return new ProductMiniCollection(filter_products($products)->latest()->paginate(10));
    }
    public function bestSeller(Request $request)
    {
        $products = Product::orderBy('num_of_sale', 'desc')->physical();

        $filtered = filter_products($products);

        return new ProductDetailCollection(
            $filtered->paginate($request->per_page ?? 10)
        );
    }

    public function frequentlyBought($slug)
    {
        $product = Product::where("slug", $slug)->first();
        $products = get_frequently_bought_products($product);
        return new ProductMiniCollection($products);
    }

    public function topFromSeller($slug)
    {
        $product = Product::where("slug", $slug)->first();
        $products = Product::where('user_id', $product->user_id)->orderBy('num_of_sale', 'desc')->physical();
        return new ProductMiniCollection(filter_products($products)->limit(10)->get());
    }


    public function searchOld(Request $request)
    {
        $category_ids = [];
        $brand_ids = [];

        if ($request->categories != null && $request->categories != "") {
            $category_ids = explode(',', $request->categories);
        }

        if ($request->brands != null && $request->brands != "") {
            $brand_ids = explode(',', $request->brands);
        }

        $sort_by = $request->sort_key;
        $name = $request->name;
        $min = $request->min;
        $max = $request->max;


        $products = Product::query();

        $products->where('published', 1)->physical();

        if (!empty($brand_ids)) {
            $products->whereIn('brand_id', $brand_ids);
        }

        if (!empty($category_ids)) {
            $n_cid = [];
            foreach ($category_ids as $cid) {
                $n_cid = array_merge($n_cid, CategoryUtility::children_ids($cid));
            }

            if (!empty($n_cid)) {
                $category_ids = array_merge($category_ids, $n_cid);
            }

            $products->whereIn('category_id', $category_ids);
        }

        if ($name != null && $name != "") {
            $products->where(function ($query) use ($name) {
                foreach (explode(' ', trim($name)) as $word) {
                    $query->where('name', 'like', '%' . $word . '%')->orWhere('tags', 'like', '%' . $word . '%')->orWhereHas('product_translations', function ($query) use ($word) {
                        $query->where('name', 'like', '%' . $word . '%');
                    });
                }
            });
            SearchUtility::store($name);
            $case1 = $name . '%';
            $case2 = '%' . $name . '%';

            $products->orderByRaw('CASE
                WHEN name LIKE "'.$case1.'" THEN 1
                WHEN name LIKE "'.$case2.'" THEN 2
                ELSE 3
                END');
        }

        if ($min != null && $min != "" && is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if ($max != null && $max != "" && is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }



        switch ($sort_by) {
            case 'price_low_to_high':
                $products->orderBy('unit_price', 'asc');
                break;

            case 'price_high_to_low':
                $products->orderBy('unit_price', 'desc');
                break;

            case 'new_arrival':
                $products->orderBy('created_at', 'desc');
                break;

            case 'popularity':
                $products->orderBy('num_of_sale', 'desc');
                break;

            case 'top_rated':
                $products->orderBy('rating', 'desc');
                break;

            default:
                $products->orderBy('created_at', 'desc');
                break;
        }

        return new ProductMiniCollection(filter_products($products)->paginate(10));
    }
	
	
	public function search(Request $request)
    {
        $category_ids = [];

        if ($request->filled('category_ids')) {
            $category_ids = explode(',', $request->category_ids);
        }

        $name = $request->input('search-key');
        $min = $request->input('price_min');
        $max = $request->input('price_max');
        $rate = $request->input('rate');
        $isAvailable = $request->input('is_available'); // 1 or 0

        $products = Product::query();

        $products->where('published', 1)->physical();

        // Category filter with children
        if (!empty($category_ids)) {
            $allCategoryIds = $category_ids;
            foreach ($category_ids as $cid) {
                $allCategoryIds = array_merge($allCategoryIds, CategoryUtility::children_ids($cid));
            }
            $products->whereIn('category_id', array_unique($allCategoryIds));
        }

        // Name search
        if (!empty($name)) {
            $products->where(function ($query) use ($name) {
                foreach (explode(' ', trim($name)) as $word) {
                    $query->where('name', 'like', '%' . $word . '%')
                        ->orWhereHas('product_translations', function ($q) use ($word) {
                            $q->where('name', 'like', '%' . $word . '%');
                        });
                }
            });

            // Optional sorting boost
            $case1 = $name . '%';
            $case2 = '%' . $name . '%';
            $products->orderByRaw('CASE
                WHEN name LIKE ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END', [$case1, $case2]);
        }

        // Price filter
        if (is_numeric($min)) {
            $products->where('unit_price', '>=', $min);
        }

        if (is_numeric($max)) {
            $products->where('unit_price', '<=', $max);
        }

        // Rate filter
        if (is_numeric($rate)) {
            $products->where('rating', '>=', $rate);
        }

        // Availability filter
        if ($isAvailable == '1') {
            $products->where('current_stock', '>', 0);
        }

        // Clone query to get min and max price of current result set
        $priceRange = (clone $products)->selectRaw('MIN(unit_price) as min_price, MAX(unit_price) as max_price')->first();

        // Paginate with custom per_page param
        $perPage = $request->input('per_page', 10);
        $paginated = filter_products($products)->paginate($perPage);

        // Return with price range metadata
        return (new ProductMiniCollection($paginated))->additional([
            'min_price' => (float) $priceRange->min_price,
            'max_price' => (float) $priceRange->max_price,
        ]);
    }


    public function variantPrice(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $str = '';
        $tax = 0;

        if ($request->has('color') && $request->color != "") {
            $str = Color::where('code', '#' . $request->color)->first()->name;
        }

        $var_str = str_replace(',', '-', $request->variants);
        $var_str = str_replace(' ', '', $var_str);

        if ($var_str != "") {
            $temp_str = $str == "" ? $var_str : '-' . $var_str;
            $str .= $temp_str;
        }
        return   $this->calc($product, $str, $request, $tax);
    }

    public function lastViewedProducts(){
        $lastViewedProducts = getLastViewedProducts();
        return new LastViewedProductCollection( $lastViewedProducts);
    }

    public function relatedProducts(Request $request,$id)
    {
        $product=Product::findOrFail($id);
        $categoryIds = $product->categories->pluck('id');
      
        $relatedProducts = Product::whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $product->id) 
            ->distinct()
            ->paginate($request->per_page ?? 10);

            return new ProductDetailCollection( $relatedProducts);
    }


    public function getCategoriesWithPriceRange()
    {
        $categories = Category::select('id', 'slug', 'name', 'banner', 'cover_image', 'icon')->get();

        $categories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'banner' => $category->banner ? url($category->banner) : null,
                'cover_image' => $category->cover_image ? url($category->cover_image) : null,
                'icon' => $category->icon ? url($category->icon) : null,
            ];
        });

        $priceStats = Product::query()
            ->where('published', 1)
            ->physical()
            ->selectRaw('MIN(unit_price) as min_price, MAX(unit_price) as max_price')
            ->first();

        return response()->json([
            'result' => true,
            'data' => [
                'categories' => $categories,
                'min_price' => (float) $priceStats->min_price,
                'max_price' => (float) $priceStats->max_price,
            ],
            'message' => translate('Categories with price range loaded successfully')
        ]);
    }

    

}
