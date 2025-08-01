<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseHistoryMiniCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'order_id' => $data->id,
                    'code' => $data->code,
                    'user_id' => intval($data->user_id),
                    //'payment_type' => ucwords(str_replace('_', ' ', $data->payment_type)),
                    //'payment_status' => translate($data->payment_status),
                    //'payment_status_string' => ucwords(str_replace('_', ' ', translate($data->payment_status))),
                    'delivery_status' => translate($data->delivery_status),
                    'delivery_status_string' => $data->delivery_status == translate('pending') ? translate("Order Placed") : ucwords(str_replace('_', ' ',  translate($data->delivery_status))),
                    'grand_total' => format_price(convert_price($data->grand_total)),
                    'num_of_items' => $data->orderDetails->sum('quantity'),
                    'date' => Carbon::createFromTimestamp($data->date)->format('d-m-Y'),
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
