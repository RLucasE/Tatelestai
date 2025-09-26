<?php

use App\Enums\EstablishmentTypeState;
use App\Models\EstablishmentType;
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
        Schema::table('establishment_types', function (Blueprint $table) {
            $table->string('state')->default(EstablishmentTypeState::ACTIVE->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establishment_types', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
