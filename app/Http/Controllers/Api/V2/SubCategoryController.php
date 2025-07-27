<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\CategoryCollection;
use App\Models\Category;

class SubCategoryController extends Controller
{
    public function index($id)
    {
         $categories = Category::with(['childrenCategories', 'products'])->where('parent_id', $id)->get();
         return new CategoryCollection($categories);
        // return new CategoryCollection(Category::where('parent_id', $id)->get());
    }
}
