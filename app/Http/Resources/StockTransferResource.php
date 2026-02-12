<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\StockTransfer;
use App\Http\Resources\StockTransferItemResource;

class StockTransferResource extends JsonResource
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
            'source' => $this->fromBranch->name,
            'destination' => $this->toBranch->name,
            'date' => $this->date->format('Y-m-d'),
            'status_code' => $this->status,
            'status_label' => $this->status === 'M' ? 'In Transit' : 'Received',
            'is_completed' => $this->status === 'R',
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'total_quantity' => $this->items->sum('quantity'),
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans()
        ];
    }
}
