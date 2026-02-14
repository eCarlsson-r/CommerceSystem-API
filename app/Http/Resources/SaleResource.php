<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Settings;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'invoice_no' => $this->invoice_number,
            'date' => $this->date,
            'branch'   => $this->branch->name,
            'employee'   => $this->employee->name,
            'customer'   => $this->customer,
            'items'      => $this->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'quantity'  => $item->quantity,
                    'price'=> $item->sale_price
                ];
            }),
            'subtotal' => $this->subtotal,
            'manual_discount' => $this->manual_discount,
            'applied_points' => $this->applied_points,
            'grand_total' => $this->grand_total,
            'payment_summary' => $this->payments
        ];
    }

    private function generateCipher($price) {
        $key = Settings::where('key', 'cipher_key')->first()->value;
        $digits = str_split(floor($price));
        $result = '';
  
        for ($i = 0; $i < count($digits); $i++) {
            $count = 1;
            // Check if the next digits are the same
            while ($i + 1 < count($digits) && $digits[$i] === $digits[$i + 1]) {
                $count++;
                $i++;
            }

            $num = (int)$digits[$i];
            $char = '';
            if ($num === 0) {
                $char = $key[9];
            } else {
                $char = $key[$num - 1];
            }
    
            // If count > 1, append the character then the number of repeats
            $result .= $count > 1 ? $char . $count : $char;
        }
  
        return $result;
    }
}
