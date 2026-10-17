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
        Schema::create('establishment_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sell_id')->unique();
            $table->foreign('sell_id')->references('id')->on('sells')->cascadeOnDelete();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('food_establishment_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['food_establishment_id', 'created_at'], 'reviews_establishment_created_idx');
            $table->index(['food_establishment_id', 'rating'], 'reviews_establishment_rating_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establishment_reviews');
    }
};
