<?php

namespace App\Policies;

use App\Enums\TransactionStatus;
use App\Models\StockIn;
use App\Models\User;

class StockInPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock-ins.view');
    }

    public function view(User $user, StockIn $stockIn): bool
    {
        if (! $user->can('stock-ins.view')) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockIn->created_by === (int) $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('stock-ins.create');
    }

    public function update(User $user, StockIn $stockIn): bool
    {
        if (! $user->can('stock-ins.create')) {
            return false;
        }

        if ($stockIn->status !== TransactionStatus::DRAFT) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockIn->created_by === (int) $user->id;
        }

        return true;
    }

    public function confirm(User $user, StockIn $stockIn): bool
    {
        if (! $user->can('stock-ins.create')) {
            return false;
        }

        if ($stockIn->status !== TransactionStatus::DRAFT) {
            return false;
        }

        if ($user->hasRole('staff_gudang')) {
            return (int) $stockIn->created_by === (int) $user->id;
        }

        return true;
    }

    public function cancel(User $user, StockIn $stockIn): bool
    {
        if ($stockIn->status === TransactionStatus::DRAFT) {
            if ($user->hasRole('staff_gudang')) {
                return (int) $stockIn->created_by === (int) $user->id;
            }

            return $user->can('stock-ins.create');
        }

        if ($stockIn->status === TransactionStatus::CONFIRMED) {
            return $user->can('stock-ins.cancel-confirmed');
        }

        return false;
    }
}
