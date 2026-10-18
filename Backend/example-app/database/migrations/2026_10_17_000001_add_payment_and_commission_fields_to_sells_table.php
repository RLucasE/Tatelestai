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
        Schema::table('sells', function (Blueprint $table) {
            $table->string('preference_id')->nullable()->index()->after('pickup_code');
            $table->string('payment_id')->nullable()->index()->after('preference_id');
            $table->string('payment_status')->default('approved')->after('payment_id');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->decimal('gross_amount', 12, 2)->default(0)->after('payment_method');
            $table->decimal('commission_percentage', 5, 2)->default(15.00)->after('gross_amount');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_percentage');
            $table->decimal('net_amount', 12, 2)->default(0)->after('commission_amount');
            $table->timestamp('refunded_at')->nullable()->after('picked_up_at');
            $table->string('cancelled_by')->nullable()->after('refunded_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn([
                'preference_id',
                'payment_id',
                'payment_status',
                'payment_method',
                'gross_amount',
                'commission_percentage',
                'commission_amount',
                'net_amount',
                'refunded_at',
                'cancelled_by',
                'cancellation_reason',
            ]);
        });
    }
};
