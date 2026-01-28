<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->created_at->format('d M Y'),
            'time' => $this->created_at->format('H:i'),
            'type' => ucfirst($this->type),
            'reference' => $this->reference_id,
            'description' => $this->description,
            // Separate into distinct columns for the UI
            'qty_in' => $this->quantity_change > 0 ? $this->quantity_change : null,
            'qty_out' => $this->quantity_change < 0 ? abs($this->quantity_change) : null,
            'running_balance' => $this->balance_after,
            'staff' => $this->user->name ?? 'System',
        ];
    }
}