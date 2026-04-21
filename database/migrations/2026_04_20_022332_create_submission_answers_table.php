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
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('instrument_question_id')
                ->constrained('instrument_questions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('answer_value')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'instrument_question_id']);
            $table->index('instrument_question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
