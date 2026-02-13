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
        
        if ($user && $user->employee) {
            $branchId = $user->employee->branch_id;
            $builder->where('branch_id', $branchId);
        }
    }
}