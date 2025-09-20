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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('service',['github','gitlab','bitbucket','jira','trello','asana']);
            $table->string('service_user_id');
            // 3. Данные для доступа (ХРАНИТЬ В ЗАШИФРОВАННОМ ВИДЕ!)
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('expires_at');
            $table->timestamp('next_check_provider_instances_at');
            $table->timestamps();
            $table->unique(['user_id', 'service']);

            $table->index('user_id');
            $table->index('service');
            $table->index(['user_id', 'service']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
