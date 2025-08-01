<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\ProductDetailCollection;
use App\Http\Resources\V2\WishlistCollection;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10); 

        $wishlists = get_wishlists()->paginate($perPage);

        $products = $wishlists->getCollection()->map(function ($wishlist) {
            return $wishlist->product;
        })->filter();

        // Reassign paginated structure with the product collection
        $paginatedProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $products,
            $wishlists->total(),
            $wishlists->perPage(),
            $wishlists->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return new ProductDetailCollection($paginatedProducts);
    }
    public function get_all_product_ids()
    {
        $ids=Wishlist::where('user_id',auth()->user()->id)->pluck('product_id');
        return response()->json($ids);

    }
    public function add($slug)
    {
        $product = Product::where('slug', $slug)->first();
        $wishlist = Wishlist::where('product_id', $product->id)->where('user_id', auth()->user()->id)->first();
        if ($wishlist != null) {
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (integer)$product->id,
                'product_slug' => $product->slug,
                'wishlist_id' => $wishlist->id
            ], 200);
        } else {
            $wishlist = Wishlist::create(
                ['user_id' =>auth()->user()->id, 'product_id' =>$product->id]
            );

            return response()->json([
                'message' => translate('Product added to wishlist'),
                'is_in_wishlist' => true,
               'product_id' => (integer)$product->id,
                'product_slug' => $product->slug,
                'wishlist_id' => $wishlist->id
            ], 200);
        }
    }

    public function remove($slug)
    {
        $product = Product::where('slug', $slug)->first();
        $wishlist = Wishlist::where('product_id', $product->id)->where('user_id',  auth()->user()->id)->first();
        if ($wishlist == null) {
            return response()->json([
                'message' => translate('Product in not in wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (integer)$product->id,
                'product_slug' => $product->slug
            ], 200);
        } else {
            Wishlist::where('product_id' , $product->id)->where( 'user_id' , auth()->user()->id)->delete();
            return response()->json([
                'message' => translate('Product is removed from wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (integer)$product->id,
                'product_slug' => $product->slug
            ], 200);
        }
    }

    public function isProductInWishlist($slug)
    {
        $product = Product::where('slug', $slug)->first();

        $wishlist = Wishlist::where('product_id', $product->id)->where('user_id',  auth()->user()->id)->first();

        if ($wishlist != null) {
            return response()->json([
                'message' => translate('Product present in wishlist'),
                'is_in_wishlist' => true,
                'product_id' => (integer)$product->id,
                'wishlist_id' => $wishlist->id
            ], 200);
        }else{
            return response()->json([
                'message' => translate('Product is not present in wishlist'),
                'is_in_wishlist' => false,
                'product_id' => (integer)$product->id,
                'wishlist_id' => $wishlist->id
            ], 200);
        }

    }
    public function toggle($id)
{
    $user = auth()->user();
    $product = Product::where('id', $id)->first();

    if (!$product) {
        return response()->json([
            'message' => translate('Product not found'),
            'is_in_wishlist' => false
        ], 404);
    }

    $wishlist = Wishlist::where('product_id', $product->id)
                        ->where('user_id', $user->id)
                        ->first();

    if ($wishlist) {
        $wishlist->delete();

        return response()->json([
            'message' => translate('Product removed from wishlist'),
            'is_in_wishlist' => false,
            'product_id' => (int) $product->id,
            'product_slug' => $product->slug
        ], 200);
    } else {
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);

        return response()->json([
            'message' => translate('Product added to wishlist'),
            'is_in_wishlist' => true,
            'product_id' => (int) $product->id,
            'product_slug' => $product->slug,
            'wishlist_id' => $wishlist->id
        ], 200);
    }
}
}
