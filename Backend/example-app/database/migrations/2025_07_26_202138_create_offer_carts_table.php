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
        Schema::create('offer_carts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('offer_id');
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->unsignedBigInteger('user_cart_id');
            $table->foreign('user_cart_id')->references('id')->on('user_carts')->onDelete('cascade');
            $table->unsignedBigInteger('quantity')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_carts');
    }
};
