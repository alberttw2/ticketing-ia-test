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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('filename');
            $table->string('original_path');
            $table->string('status'); // NEW, PROCESSED, ERROR, REVIEW
            $table->text('raw_text')->nullable();
            $table->text('ocr_text')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->date('ticket_date')->nullable();
            $table->text('processing_log')->nullable();
            $table->text('ai_analysis')->nullable(); // JSON data with AI processing results
            $table->boolean('manually_reviewed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
