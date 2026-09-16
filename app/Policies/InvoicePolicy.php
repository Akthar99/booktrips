<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the partner can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->business?->id === $invoice->business_id;
    }
}
