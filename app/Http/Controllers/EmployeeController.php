<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'mobile' => 'required|string',
            'email' => 'nullable|email',
            'join_date' => 'required|date',
            'create_account' => 'boolean',
            'username' => 'required_if:create_account,true|unique:users,username',
            'password' => 'required_if:create_account,true|min:6',
            'type' => 'required_if:create_account,true|in:admin,staff',
        ]);

        return DB::transaction(function () use ($validated) {
            $userId = null;

            // 1. Create User if requested
            if ($validated['create_account']) {
                $user = User::create([
                    'username' => $validated['username'],
                    'password' => Hash::make($validated['password']),
                    'type' => $validated['type'],
                ]);
                $userId = $user->id;
            }

            // 2. Create Employee
            $employee = Employee::create([
                'name' => $validated['name'],
                'branch_id' => $validated['branch_id'],
                'mobile' => $validated['mobile'],
                'email' => $validated['email'],
                'join_date' => $validated['join_date'],
                'user_id' => $userId, // Links to the user we just made
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'Employee onboarded successfully',
                'employee' => $employee->load('branch')
            ], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        //
    }

    public function offboard(Request $request, $id)
    {
        $request->validate([
            'quit_date' => 'required|date',
            'reason' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $id) {
            $employee = Employee::findOrFail($id);

            // 1. Mark Employee as Inactive
            $employee->update([
                'status' => 'inactive',
                'quit_date' => $request->quit_date,
                'notes' => $request->reason
            ]);

            // 2. Disable the User Account
            if ($employee->user_id) {
                $user = User::find($employee->user_id);
                $user->tokens()->delete(); 
                $user->update(['status' => 'banned']); 
            }

            return response()->json(['message' => 'Employee access revoked and status updated.']);
        });
    }
}
