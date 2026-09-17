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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raised_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('against_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 40)->default('awaiting_response');
            $table->text('summary');
            $table->text('details')->nullable();
            $table->json('evidence')->nullable();
            $table->text('response')->nullable();
            $table->json('response_evidence')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('response_deadline_at')->nullable();
            $table->string('resolution', 40)->nullable();
            $table->string('penalty', 40)->nullable();
            $table->unsignedInteger('penalty_amount_lkr')->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['against_user_id', 'status']);
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
