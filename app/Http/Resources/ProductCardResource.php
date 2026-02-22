<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $topStock = $this->product->stocks()->orderBy('quantity', 'desc')->first();

        return [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price' => $this->sale_price, // Price comes from Stock
            'image' => $this->product->media->first()?->path,
            'quantity' => $this->quantity,
            'discount' => $this->discount_percent,
            'primary_branch' => $topStock ? $topStock->branch : null,
            'is_out_of_stock' => $this->product->stocks()->sum('quantity') <= 0,
            'since_date' => $this->created_at->toISOString(),
            'products_sold' => $this->logs()->where('type', 'OUT')->sum('quantity_change'),
            'media' => $this->product->media,
            'category' => $this->product->category,
            'description' => $this->product->description
        ];
    }
}
