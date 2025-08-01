<?php

namespace App\Http\Resources\V2;

use App\Models\Product;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\CategoryUtility;

class CategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'id' => $data->id,
                    'slug' => $data->slug,
                    'name' => $data->getTranslation('name'),
                    'cover_image' => uploaded_asset($data->cover_image),
                    'banner' => uploaded_asset($data->banner),
                    'icon' => uploaded_asset($data->icon),
                    "children" => $data->childrenCategories->isNotEmpty()
                        ? new CategoryCollection($data->childrenCategories)
                        : new ProductCollection($data->products),
                    // 'number_of_children' => CategoryUtility::get_immediate_children_count($data->id),
                    // 'links' => [
                    //     'products' => route('api.products.category', $data->id),
                    //     'sub_categories' => route('subCategories.index', $data->id)
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
