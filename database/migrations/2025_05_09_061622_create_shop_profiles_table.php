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
        Schema::create('shop_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('shop_name');
            $table->text('description');
            $table->string('tax_id_number')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone');
            $table->string('banner_url')->nullable();
            $table->string('profile_url')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('facebook_link')->nullable();
            $table->string('telegram_link')->nullable();
            $table->string('instagram_link')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->boolean('verified')->default(false);
            $table->float('rating', 8, 2)->nullable()->default(0.0);
            $table->integer('subscribers')->nullable()->default(0);
            $table->integer('total_reviews')->nullable()->default(0);
            $table->integer('views')->default(0);
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_profiles');
    }
};
