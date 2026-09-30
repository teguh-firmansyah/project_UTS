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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('grade', 10);
            $table->string('major', 50);
            $table->string('name', 20);
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->string('academic_year', 9);
            $table->timestamps();

            $table->unique(['grade', 'major', 'sequence', 'academic_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
