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
        Schema::create('developer_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('external_id');
            $table->string('repository_name');
            $table->enum('type', ['commit', 'pull_request']);
            $table->boolean('is_approved')->default(false);
            $table->string('title');
            $table->text('url');
            $table->timestamp('completed_at');
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->timestamps();

            $table->unique(['integration_id', 'type', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_activities');
    }
};
