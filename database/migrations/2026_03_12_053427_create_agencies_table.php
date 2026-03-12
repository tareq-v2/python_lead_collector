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
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->nullable();
            $table->string('website', 500)->unique()->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('github_url', 500)->nullable();
            $table->string('clutch_url', 500)->nullable();
            $table->string('company_size', 50)->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->text('description')->nullable();
            $table->decimal('clutch_rating', 3, 1)->nullable();
            $table->integer('reviews_count')->default(0);
            $table->string('source', 100)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
