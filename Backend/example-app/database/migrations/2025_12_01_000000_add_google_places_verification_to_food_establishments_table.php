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
            $table->string('google_place_id')->nullable()->after('address');
            $table->json('google_place_data')->nullable()->after('google_place_id');
            $table->string('establishment_photo')->nullable()->after('google_place_data');
            $table->string('owner_selfie')->nullable()->after('establishment_photo');
            $table->string('phone')->nullable()->after('owner_selfie');
            $table->text('description')->nullable()->after('phone');
            $table->decimal('latitude', 10, 8)->nullable()->after('description');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('longitude');
            $table->text('verification_notes')->nullable()->after('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_establishments', function (Blueprint $table) {
            $table->dropColumn([
                'google_place_id',
                'google_place_data',
                'establishment_photo',
                'owner_selfie',
                'phone',
                'description',
                'latitude',
                'longitude',
                'verification_status',
                'verification_notes'
            ]);
        });
    }
};

