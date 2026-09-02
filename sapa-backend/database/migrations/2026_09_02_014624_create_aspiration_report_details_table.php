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
        Schema::create('aspiration_report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()
                ->constrained('reports')->cascadeOnDelete();
            $table->enum('category', ['academic', 'facility_policy', 'school_policy', 'other']);
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspiration_report_details');
    }
};
