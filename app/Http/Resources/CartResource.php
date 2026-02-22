<?php

namespace App\Http\Resources;

use App\Models\Stock;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = Stock::where('branch_id', $this->branch_id)
                    ->where('product_id', $this->product_id)
                    ->first();

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'branch_id' => $this->branch_id,
            'name' => $this->product->name,
            'image' => $this->product->image,
            'quantity' => $this->quantity,
            'price' => $stock ? $stock->sale_price : 0,
            'branch' => $this->whenLoaded('branch'),
        ];
    }
}
