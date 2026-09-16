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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 40);
            $table->text('description');
            $table->string('highlight', 500)->nullable();
            $table->string('location', 160);
            $table->string('address')->nullable();
            $table->string('district', 120)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('schedule_type', 20)->default('always');
            $table->date('schedule_start')->nullable();
            $table->date('schedule_end')->nullable();
            $table->json('weekdays')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedSmallInteger('duration_nights')->default(0);
            $table->unsignedBigInteger('price_lkr');
            $table->string('price_type', 20)->default('per_package');
            $table->string('discount_type', 20)->default('none');
            $table->decimal('discount_value', 8, 2)->default(0);
            $table->boolean('discount_enabled')->default(false);
            $table->date('discount_start')->nullable();
            $table->date('discount_end')->nullable();
            $table->unsignedSmallInteger('min_guests')->default(1);
            $table->unsignedSmallInteger('max_guests')->default(8);
            $table->json('included')->nullable();
            $table->json('excluded')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->string('meeting_point')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'active']);
            $table->index(['category', 'active']);
            $table->index(['district', 'active']);
            $table->index(['featured', 'active']);
            $table->index(['active', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
