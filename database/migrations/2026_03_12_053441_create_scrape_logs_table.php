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
        Schema::create('scrape_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100)->nullable();
            $table->string('status', 50)->default('pending');
            $table->integer('records_found')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->default(0);
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrape_logs');
    }
};
