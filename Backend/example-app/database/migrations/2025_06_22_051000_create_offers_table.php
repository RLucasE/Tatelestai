<?php

use App\Enums\OfferState;
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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('food_establishment_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->onDelete('cascade');
            $table->unsignedBigInteger('pack_template_id')->nullable();
            $table->foreign('pack_template_id')->references('id')->on('pack_templates')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('price');
            $table->unsignedInteger('minimum_value');
            $table->json('allergens')->nullable();
            $table->decimal('estimated_weight_kg', 6, 2)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('pickup_start_datetime')->nullable();
            $table->timestamp('expiration_datetime');
            $table->string('state')->default(OfferState::ACTIVE->value);

            $table->index(['state', 'expiration_datetime'], 'offers_state_expiration_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
