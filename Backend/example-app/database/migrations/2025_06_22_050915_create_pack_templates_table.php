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
        Schema::create('pack_templates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('food_establishment_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->json('allergens')->nullable();
            $table->decimal('estimated_weight_kg', 6, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pack_templates');
    }
};
