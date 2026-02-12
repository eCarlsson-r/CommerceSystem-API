<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
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
            'return_number' => $this->purchaseReturn->return_number,
            'return_date' => $this->purchaseReturn->return_date,
            'product' => $this->product,
            'branch' => $this->purchaseReturn->branch,
            'reason' => $this->purchaseReturn->reason,
            'condition' => $this->condition,
            'supplier' => $this->purchaseReturn->supplier,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price
        ];
    }
}
