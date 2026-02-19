<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Customer::all());
    }

    public function show($id)
    {
        return response()->json(Customer::findOrFail($id));
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
                'role' => 'customer'
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

    public function history($id)
    {
        $customer = Customer::findOrFail($id);
        $sales = $customer->sales;
        $sales->load('items.product');
        return response()->json($sales);
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
