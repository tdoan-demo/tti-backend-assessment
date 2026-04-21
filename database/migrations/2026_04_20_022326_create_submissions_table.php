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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('instrument_id')
                ->constrained('instruments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->index(['patient_id', 'submitted_at']);
            $table->index('instrument_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
