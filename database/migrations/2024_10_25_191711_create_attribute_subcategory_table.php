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
        Schema::create('attribute_subcategory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained('subcategories')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_subcategory');
    }
};
