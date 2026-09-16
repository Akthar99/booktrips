<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    /**
     * Receipt files are private: only the owning partner (or an admin) may view them.
     */
    public function view(User $user, Receipt $receipt): bool
    {
        return $user->business?->id === $receipt->business_id;
    }
}
