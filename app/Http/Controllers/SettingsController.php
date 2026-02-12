<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SettingsController extends Controller
{
    public function index() {
        return Settings::all();
    }

    public function update(Request $request, $id) {
        // Ensure the key is exactly 10 unique letters
        $request->validate([
    'value' => [
        'required',
        'size:10',
        'alpha',
        function ($attribute, $value, $fail) {
            // Check if all characters are unique (case-insensitive)
            if (strlen($value) !== strlen(count_chars(strtolower($value), 3))) {
                $fail('The '.$attribute.' must contain only unique characters.');
            }
        },
    ],
]);
    
        Settings::updateOrCreate(
            ['key' => $id],
            ['value' => strtoupper($request->value)]
        );
        return response()->json(['message' => 'Setting updated']);
    }
}
