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
        Schema::create('facility_report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()
                ->constrained('reports')->cascadeOnDelete();
            $table->string('location', 150);
            $table->enum('category', ['electricity', 'furniture', 'sanitation', 'building', 'other']);
            $table->enum('damage_level', ['minor', 'moderate', 'severe'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_report_details');
    }
};
