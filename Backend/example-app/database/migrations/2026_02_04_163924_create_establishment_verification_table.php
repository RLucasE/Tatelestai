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
        Schema::create('establishment_verification_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_establishment_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establishment_verification_files');
    }
};
