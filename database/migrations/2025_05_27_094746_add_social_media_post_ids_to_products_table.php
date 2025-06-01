<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialMediaPostIdsToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('facebook_post_id')->nullable()->after('id'); 
            $table->string('telegram_post_id')->nullable()->after('facebook_post_id'); 
            $table->string('insta_post_id')->nullable()->after('telegram_post_id');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('facebook_post_id');
            $table->dropColumn('telegram_post_id');
            $table->dropColumn('insta_post_id');
        });
    }
}