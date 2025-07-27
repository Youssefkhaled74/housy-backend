<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SliderCollection extends ResourceCollection
{
   public function toArray($request)
{
    return $this->collection->map(function ($data) {
        return [
            'id' => $data->id,
            'image' => !empty($data->photo) 
                ? (is_array($data->photo) ? uploaded_asset($data->photo[0]) : uploaded_asset($data->photo)) 
                : null,
            'related_id' => $data->module_id,
            'type' => $data->module_name,
            'link' => $data->link,
        ];
    });
}


    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}

