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
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('net_amount');
        });

        Schema::table('sell_details', function (Blueprint $table) {
            $table->json('allergens')->nullable()->after('pack_description');
            $table->decimal('estimated_weight_kg', 8, 2)->nullable()->after('allergens');
            $table->decimal('minimum_value', 10, 2)->nullable()->after('estimated_weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sells', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });

        Schema::table('sell_details', function (Blueprint $table) {
            $table->dropColumn([
                'allergens',
                'estimated_weight_kg',
                'minimum_value',
            ]);
        });
    }
};
