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
        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('visible_from')->nullable()->after('pickup_start_datetime');
            $table->index(['state', 'visible_from', 'expiration_datetime'], 'offers_visibility_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex('offers_visibility_index');
            $table->dropColumn('visible_from');
        });
    }
};
