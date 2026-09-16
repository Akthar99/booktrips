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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 40);
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120);
            $table->string('district', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('website', 2048)->nullable();
            $table->string('cover_image', 2048)->nullable();
            $table->string('instagram', 2048)->nullable();
            $table->string('facebook', 2048)->nullable();
            $table->string('tiktok', 2048)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();

            $table->index(['approved', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
