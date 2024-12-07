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
        // Rename the table to plural
        Schema::rename('attribute_subcategory', 'attribute_subcategories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename the table back to singular
        Schema::rename('attribute_subcategories', 'attribute_subcategory');
    }
};
