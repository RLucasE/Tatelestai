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
        Schema::create('sells', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bought_by');
            $table->unsignedBigInteger('sold_by');
            $table->foreign('bought_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sold_by')->references('id')->on('food_establishments')->onDelete('cascade');
            $table->string('pickup_code');
            $table->boolean('is_picked_up')->default(false);
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sells');
    }
};
