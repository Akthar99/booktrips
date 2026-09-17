<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for what the hot paths actually query: catalogue price filtering and sorting,
 * the admin finance queues, partner dashboard counts and the dispute verdict queue.
 * Without them these lookups become full table scans as the tables grow.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Catalogue price filter and price sorting over active listings.
        $this->addIndex('packages', ['active', 'price_lkr'], 'packages_active_price_index');

        // Partner dashboard counts: status breakdown plus upcoming check-ins.
        $this->addIndex('bookings', ['business_id', 'status', 'check_in'], 'bookings_business_status_checkin_index');

        // Admin "receipts waiting for review" queue.
        $this->addIndex('receipts', ['status', 'created_at'], 'receipts_status_created_index');

        // Finance lists sort every invoice by period.
        $this->addIndex('invoices', 'period', 'invoices_period_index');

        // Verdict queue: open reports split by whether the other side answered.
        $this->addIndex('disputes', ['status', 'responded_at'], 'disputes_status_responded_index');

        // "Reviews written" on the admin traveller history page.
        $this->addIndex('reviews', 'user_id', 'reviews_user_index');

        // Inbox ordering when threads share a last message timestamp.
        $this->addIndex('support_tickets', 'last_message_at', 'support_tickets_last_message_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndex('packages', 'packages_active_price_index');
        $this->dropIndex('bookings', 'bookings_business_status_checkin_index');
        $this->dropIndex('receipts', 'receipts_status_created_index');
        $this->dropIndex('invoices', 'invoices_period_index');
        $this->dropIndex('disputes', 'disputes_status_responded_index');
        $this->dropIndex('reviews', 'reviews_user_index');
        $this->dropIndex('support_tickets', 'support_tickets_last_message_index');
    }

    /**
     * Add an index only when it is missing, so the migration is safe to re-run and safe
     * on databases where one of these was created by hand.
     *
     * @param  array<int, string>|string  $columns
     */
    private function addIndex(string $table, array|string $columns, string $name): void
    {
        if (Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }
};
