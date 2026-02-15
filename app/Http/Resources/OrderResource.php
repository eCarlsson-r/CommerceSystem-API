<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request) {
        $addressItems = preg_split('/\R/', $this->shipping_address);
        $address = $addressItems[0];
        $addressItems = explode(', ', $addressItems[1]);
        $city = $addressItems[0];
        $postal = $addressItems[1];
        return [
            'order_id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'branch' => $this->branch,
            'customer' => $this->customer,
            'shipping_address' => $address,
            'shipping_city' => $city,
            'shipping_postal' => $postal,
            'tracking_number' => $this->tracking_no,
            'courier_service' => $this->courier_service,
            'items' => $this->items,
            'grand_total' => $this->grand_total
        ];
    }
}
