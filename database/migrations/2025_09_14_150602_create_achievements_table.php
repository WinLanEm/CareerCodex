<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('result')->nullable();
            $table->unsignedInteger('hours_spent')->default(0);
            $table->date('date')->nullable();
            $table->jsonb('skills')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_from_provider')->default(false);
            $table->enum('provider',['jira','asana'])->nullable();
            $table->string('project_name')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();

            $table->unique(['title','link','workspace_id']);
            $table->index('workspace_id');
            $table->index('date');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX achievements_skills_gin_idx ON achievements USING GIN (skills)');
        DB::statement('CREATE INDEX achievements_title_trgm_idx ON achievements USING GIN (title gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
