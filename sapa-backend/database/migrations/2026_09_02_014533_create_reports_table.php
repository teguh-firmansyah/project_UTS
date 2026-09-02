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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_code', 20)->unique();
            $table->foreignId('reporter_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->enum('type', ['aspiration', 'facility', 'bullying']);
            $table->string('title', 200);
            $table->text('description');
            $table->enum('status', ['pending', 'reviewing', 'in_progress', 'resolved', 'rejected'])
                ->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                ->default('medium');
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index('reporter_id');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
