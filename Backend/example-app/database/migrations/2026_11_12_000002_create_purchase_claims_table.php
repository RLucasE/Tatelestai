<?php

use App\Enums\ClaimStatus;
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
        Schema::create('purchase_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sell_id')->unique();
            $table->foreign('sell_id')->references('id')->on('sells')->onDelete('cascade');

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('food_establishment_id');
            $table->foreign('food_establishment_id')->references('id')->on('food_establishments')->onDelete('cascade');

            $table->string('reason');
            $table->text('description');
            $table->string('status')->default(ClaimStatus::PENDING->value);
            $table->boolean('was_picked_up_at_claim')->default(false);

            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('is_abusive')->default(false);

            $table->timestamps();

            $table->index(['customer_id', 'created_at'], 'purchase_claims_customer_created_idx');
            $table->index(['status', 'created_at'], 'purchase_claims_status_created_idx');
            $table->index(['food_establishment_id', 'status'], 'purchase_claims_est_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_claims');
    }
};
