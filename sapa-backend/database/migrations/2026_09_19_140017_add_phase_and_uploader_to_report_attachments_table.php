<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_attachments', function (Blueprint $table) {
            $table->enum('phase', ['before', 'after'])->default('before')->after('report_id');
            $table->foreignId('uploaded_by')->nullable()->after('phase')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn('phase');
        });
    }
};