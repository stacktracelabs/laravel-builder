<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('type');
            $table->string('builder_id');
            $table->string('model');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('path')->nullable();
            $table->json('content')->nullable();
            $table->json('builder_data')->nullable();
            $table->json('fields')->nullable();
            $table->string('locale')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_contents');
    }
};
