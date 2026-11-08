<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\StockOut;
use App\Models\User;

class StockOutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-outs.view');
    }

    public function view(User $user, StockOut $stockOut): bool
    {
        if (! $user->can('stock-outs.view')) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockOut->created_by === (int) $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('stock-outs.create');
    }

    public function update(User $user, StockOut $stockOut): bool
    {
        if (! $user->can('stock-outs.create')) {
            return false;
        }

        if ($stockOut->status !== TransactionStatus::DRAFT) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockOut->created_by === (int) $user->id;
        }

        return true;
    }

    public function confirm(User $user, StockOut $stockOut): bool
    {
        if (! $user->can('stock-outs.create')) {
            return false;
        }

        if ($stockOut->status !== TransactionStatus::DRAFT) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockOut->created_by === (int) $user->id;
        }

        return true;
    }

    public function cancel(User $user, StockOut $stockOut): bool
    {
        if ($stockOut->status === TransactionStatus::DRAFT) {
            if ($user->hasRole('staff_gudang')) {
                return (int) $stockOut->created_by === (int) $user->id;
            }

            return $user->can('stock-outs.create');
        }

        if ($stockOut->status === TransactionStatus::CONFIRMED) {
            return $user->can('stock-outs.cancel-confirmed');
        }

        return false;
    }
}
