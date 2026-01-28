<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'image',           // Must be an image (jpg, png, webp, etc.)
                'mimes:jpg,jpeg,png,webp', 
                'max:2048'         // 2MB Max
            ],
            'model_id' => 'required|integer',
            'model_type' => [
                'required',
                'string',
                'in:product,employee,branch,supplier' // Only these types allowed
            ],
        ];
    }
}
