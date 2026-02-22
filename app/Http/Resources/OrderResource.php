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
        if ($this->shipping_address) {
            $addressItems = preg_split('/\R/', $this->shipping_address);
            $address = $addressItems[0];
            $addressItems = explode(', ', $addressItems[1]);
            $city = $addressItems[0];
            $postal = $addressItems[1];

            return [
                'id' => $this->id,
                'order_number' => $this->order_number,
                'status' => $this->status,
                'customer' => $this->customer,
                'delivery_details' => [
                    'address' => $address,
                    'city' => $city,
                    'postal' => $postal
                ],
                'tracking_number' => $this->tracking_number,
                'courier_service' => $this->courier_service,
                'items' => $this->items,
                'total_amount' => $this->total_amount
            ];
        } else {
            return [
                'id' => $this->id,
                'order_number' => $this->order_number,
                'status' => $this->status,
                'branch' => $this->branch,
                'customer' => $this->customer,
                'items' => $this->items,
                'total_amount' => $this->total_amount
            ];
        }

    }
}
