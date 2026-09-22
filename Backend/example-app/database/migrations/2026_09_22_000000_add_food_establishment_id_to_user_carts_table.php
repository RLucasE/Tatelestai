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
        Schema::table('user_carts', function (Blueprint $table) {
            $table->unsignedBigInteger('food_establishment_id')->nullable()->after('user_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->onDelete('cascade');
            $table->index(['user_id', 'food_establishment_id', 'state'], 'user_carts_user_establishment_state_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_carts', function (Blueprint $table) {
            $table->dropForeign(['food_establishment_id']);
            $table->dropIndex('user_carts_user_establishment_state_index');
            $table->dropColumn('food_establishment_id');
        });
    }
};
