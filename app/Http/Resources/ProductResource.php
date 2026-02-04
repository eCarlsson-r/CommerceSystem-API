<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'base_price' => $this->base_price,
            'min_stock_alert' => $this->min_stock_alert,
            // Map the images to return full URLs
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . $img->path),
            ]),
            'category' => $this->category?->name,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
