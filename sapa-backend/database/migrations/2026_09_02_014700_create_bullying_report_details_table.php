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
        Schema::create('bullying_report_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()
                ->constrained('reports')->cascadeOnDelete();
            $table->enum('reporter_relation', ['victim', 'witness']);
            $table->date('incident_date')->nullable();
            $table->foreignId('handled_by_counselor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('handling_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bullying_report_details');
    }
};
