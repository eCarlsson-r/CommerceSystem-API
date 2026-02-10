<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Customer::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'mobile' => 'required|unique:customers,mobile',
            'email' => 'nullable|email|unique:users,username',
        ]);

        return DB::transaction(function () use ($validated) {
            // Create User for E-commerce access
            $user = User::create([
                'username' => $validated['email'] ?? $validated['mobile'],
                'password' => Hash::make('123456'), // Default temp password
                'type' => 'customer'
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'],
                'status' => 'active'
            ]);

            return response()->json($customer, 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
