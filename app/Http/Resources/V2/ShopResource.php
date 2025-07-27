<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => uploaded_asset($this->logo),
            'phone' => $this->seller_package ? $this->phone : '',
            'address' => $this->seller_package ? translate($this->address) : '',
            'lat' => $this->seller_package ? $this->lat : '',
            'long' => $this->seller_package ? $this->long : '',
            'rating' => (double) $this->rating,
            'products'=> $this->user->products()->count(),
            'orders'=> $this->user->seller_orders()->where("delivery_status","delivered")->count(),



        ];

    }
}
