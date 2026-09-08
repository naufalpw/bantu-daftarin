<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table): void {
            $table->string('context_type', 32)->default('APPLICATION');
        });

        Schema::table('chat_threads', function (Blueprint $table): void {
            $table->foreignId('application_id')->nullable()->change();
            $table->index(['client_user_id', 'context_type'], 'chat_threads_client_context_index');
        });
    }

    public function down(): void
    {
        if (DB::table('chat_threads')->whereNull('application_id')->exists()) {
            throw new RuntimeException('General support threads must be retained or migrated before rolling back this schema change.');
        }

        Schema::table('chat_threads', function (Blueprint $table): void {
            $table->dropIndex('chat_threads_client_context_index');
            $table->foreignId('application_id')->nullable(false)->change();
            $table->dropColumn('context_type');
        });
    }
};
