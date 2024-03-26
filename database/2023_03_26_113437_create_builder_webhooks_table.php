<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_webhooks', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->json('headers');
            $table->json('payload');
            $table->text('exception')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_webhooks');
    }
};
