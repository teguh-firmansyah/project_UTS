<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('identity_number')
                ->constrained('classes')->nullOnDelete();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['class_name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('class_name', 50)->nullable();
            $table->dropConstrainedForeignId('class_id');
        });
    }
};
