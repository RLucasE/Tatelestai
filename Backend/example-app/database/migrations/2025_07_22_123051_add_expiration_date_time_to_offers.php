<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            //
            $table->dateTime('expiration_datetime')->nullable()->after('time');
        });
        
        // Usar COALESCE en lugar de IFNULL y convertir explícitamente a timestamp
        DB::statement("UPDATE offers SET expiration_datetime = (expiration_date || ' ' || COALESCE(time, '23:59:59'))::timestamp");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            //
            $table->dropColumn('expiration_datetime');
        });
    }
};
