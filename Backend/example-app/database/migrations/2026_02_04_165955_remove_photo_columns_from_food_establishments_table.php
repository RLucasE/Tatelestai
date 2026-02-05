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
            $table->dropColumn(['establishment_photo', 'owner_selfie']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_establishments', function (Blueprint $table) {
            $table->string('establishment_photo')->nullable()->after('google_place_data');
            $table->string('owner_selfie')->nullable()->after('establishment_photo');
        });
    }
};
