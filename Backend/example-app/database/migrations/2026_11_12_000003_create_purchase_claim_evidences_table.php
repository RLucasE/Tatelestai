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
        Schema::create('purchase_claim_evidences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_claim_id')->index();
            $table->foreign('purchase_claim_id')
                ->references('id')
                ->on('purchase_claims')
                ->onDelete('cascade');

            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_claim_evidences');
    }
};
