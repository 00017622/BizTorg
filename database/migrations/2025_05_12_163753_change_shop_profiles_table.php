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
        Schema::table('shop_profiles', function (Blueprint $table) {
            $table->bigInteger('rating_sum')->unsigned()->default(0)->after('rating');
            $table->bigInteger('rating_count')->unsigned()->default(0)->after('rating_sum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_profiles', function (Blueprint $table) {
            $table->dropColumn(['rating_sum', 'rating_count']);
        });
    }
};
