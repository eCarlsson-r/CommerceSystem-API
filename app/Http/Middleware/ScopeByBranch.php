<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Foundation\Http\Middleware\Middleware;

class ScopeByBranch extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // If Super-Admin, let them through to see everything
        if ($user->type === 'admin') {
            return $next($request);
        }

        // If Staff/Manager, inject their branch_id into the request automatically
        // This ensures they can't "guess" other branch IDs in the URL
        $request->merge(['scoped_branch_id' => $user->employee->branch_id]);

        return $next($request);
    }
}