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
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();

            $table->string('repository_name');
            $table->string('git_url');
            $table->string('prod_branch');
            $table->foreignId('prod_server_id')->nullable();
            $table->string('dev_branch');
            $table->foreignId('dev_server_id')->nullable();

            $table->timestamps();

            $table->index('prod_server_id');
            $table->foreign('prod_server_id')->references('id')->on('servers')->onDelete('set null');
            $table->index('dev_server_id');
            $table->foreign('dev_server_id')->references('id')->on('servers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
