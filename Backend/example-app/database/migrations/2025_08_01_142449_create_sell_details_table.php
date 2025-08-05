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
        Schema::create('sell_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('sell_id');
            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->foreign('sell_id')->references('id')->on('sells')->onDelete('cascade');
            $table->bigInteger('offerquantity');
            $table->bigInteger('product_quantity');
            $table->bigInteger('product_price');
            $table->string('product_name');
            $table->string('product_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sell_details');
    }
};
