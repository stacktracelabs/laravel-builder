<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id');
            $table->string('title')->nullable();
            $table->string('path')->nullable();
            $table->json('content')->nullable();
            $table->json('builder_data')->nullable();
            $table->string('locale')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_pages');
    }
};
