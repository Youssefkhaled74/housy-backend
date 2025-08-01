<?php

namespace App\Http\Resources\V2;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WalletCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                return [
                    'amount' => single_price(($data->amount)),
                    'formatted_amount' => $data->formatted_amount,
                    'payment_method' => ucwords(str_replace('_', ' ', $data->payment_method)),
                    'payment_details'=>$data->payment_details,
                    'approval_string' => $data->offline_payment ? ($data->approval == 1 ? "Approved" : "Pending") : "N/A",
                    'date' => Carbon::createFromTimestamp(strtotime($data->created_at))->format('d-m-Y'),
                    'type'=>$data->type,
                    'transaction_type' => $data->transaction_type,
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'result' => true,
            'status' => 200
        ];
    }
}
