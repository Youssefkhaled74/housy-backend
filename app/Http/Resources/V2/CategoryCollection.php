<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Utility\CategoryUtility;

class CategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {

        return [
            'data' => $this->collection->map(function ($data) {
                $banner = '';
              
                if (uploaded_asset($data->banner)) {
                    $banner = uploaded_asset($data->banner);
                }
              
                $icon = '';
                if (uploaded_asset(uploaded_asset($data->icon))) {
                    $icon = uploaded_asset($data->icon);
                }
                return [
                    'id' => $data->id,
                    'slug' => $data->slug,
                    'name' => translate($data->name),//$data->getTranslation('name'),
                    'banner' => $banner,
                    'cover_image' => uploaded_asset($data->cover_image),
                    'icon' => $icon,
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
