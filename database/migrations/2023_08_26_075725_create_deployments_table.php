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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('repository_id');
            $table->string('code');
            $table->string('head_commit_id');
            $table->string('committer');
            $table->foreignId('last_command_id')->nullable();
            $table->string('status')->default(\App\Enums\DeploymentStatusEnum::Awaiting);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment');
    }
};
