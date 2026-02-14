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
        return [
            'order_id' => $this->id,
            'status' => $this->status,
            'shipping_address' => $this->address,
            'tracking_number' => $this->tracking_no,
            'items' => $this->items->map(fn($item) => [
                'name' => $item->product->name,
                'image' => $item->product->image_url,
                'price' => $item->unit_price,
                'quantity' => $item->quantity
            ]),
            'grand_total' => $this->grand_total
        ];
    }
}
