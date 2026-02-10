<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();
        
        // If the user is a 'staff' or 'manager', they only see their branch data.
        // 'super-admin' (Medan HQ) can see everything.
        if ($user && $user->role !== 'super-admin') {
            $builder->where('branch_id', $user->branch_id);
        }
    }
}