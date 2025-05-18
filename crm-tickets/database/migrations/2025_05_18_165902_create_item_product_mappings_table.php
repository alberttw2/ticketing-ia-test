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
        Schema::create('item_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->constrained()->onDelete('cascade');
            $table->string('item_name');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->float('confidence')->default(1.0);
            $table->boolean('manually_verified')->default(false);
            $table->timestamps();
            
            // Unique constraint to avoid duplicate mappings
            $table->unique(['establishment_id', 'item_name', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_product_mappings');
    }
};
