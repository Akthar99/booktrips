<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 12)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests')->default(1);
            $table->unsignedBigInteger('base_total_lkr');
            $table->unsignedBigInteger('discount_lkr')->default(0);
            $table->boolean('discount_applied')->default(false);
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->unsignedBigInteger('total_lkr');
            $table->string('status', 20)->default('requested');
            $table->string('payment_method', 30)->default('pay_at_destination');
            $table->string('guest_name');
            $table->string('guest_phone', 40);
            $table->text('notes')->nullable();
            $table->boolean('commission_added')->default(false);
            $table->unsignedBigInteger('commission_lkr')->default(0);
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['package_id', 'check_in', 'check_out']);
            $table->index(['business_id', 'status']);
            $table->index(['status', 'escalated']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
