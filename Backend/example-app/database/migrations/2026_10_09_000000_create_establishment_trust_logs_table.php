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
        Schema::create('establishment_trust_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_establishment_id')->index();
            $table->foreign('food_establishment_id')
                ->references('id')
                ->on('food_establishments')
                ->onDelete('cascade');
            $table->string('source_type'); // Criterio: 'tax_certificate', 'google_places_profile', etc.
            $table->integer('points'); // Puntos asignados (+30, +20, +10)
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->foreign('verified_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->jsonb('metadata')->nullable(); // Metadatos auditables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establishment_trust_logs');
    }
};
