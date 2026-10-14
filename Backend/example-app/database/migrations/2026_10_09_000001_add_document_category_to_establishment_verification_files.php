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
        Schema::table('establishment_verification_files', function (Blueprint $table) {
            $table->string('document_category')->nullable()->after('file_type');
            $table->string('status')->default('pending')->after('document_category');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('establishment_verification_files', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'document_category',
                'status',
                'rejection_reason',
                'reviewed_at',
                'reviewed_by',
            ]);
        });
    }
};
