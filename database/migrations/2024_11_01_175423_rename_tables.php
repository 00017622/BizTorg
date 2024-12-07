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
        Schema::table('product_attribute_value', function (Blueprint $table) {
            Schema::rename('product_attribute_value', 'product_attribute_values');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attribute_value', function (Blueprint $table) {
            Schema::rename('product_attribute_values', 'product_attribute_value');
        });
    }
};
