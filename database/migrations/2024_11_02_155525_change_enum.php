<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->nullable()->change();

            // Drop the index if it already exists
            DB::statement('DROP INDEX IF EXISTS products_type_index');
            
            // Now create the index
            $table->index('type', 'products_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_type_index'); // Drop the index on rollback
            $table->enum('type', ['sale', 'purchase'])->nullable()->change();
        });
    }
};
