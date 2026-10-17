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
        Schema::table('food_establishments', function (Blueprint $table) {
            $table->decimal('rating_average', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('recent_rating_average', 3, 2)->nullable();
            $table->unsignedInteger('recent_reviews_count')->default(0);

            $table->index(['recent_rating_average', 'rating_average'], 'establishment_dual_rating_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_establishments', function (Blueprint $table) {
            $table->dropIndex('establishment_dual_rating_idx');
            $table->dropColumn([
                'rating_average',
                'reviews_count',
                'recent_rating_average',
                'recent_reviews_count',
            ]);
        });
    }
};
