<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['chat_thread_id', 'read_at', 'deleted_at'], 'chat_messages_thread_read_deleted_index');
        });

        Schema::create('chat_thread_user_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_thread_id')->constrained('chat_threads')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_thread_id', 'user_id']);
            $table->index(['user_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_thread_user_states');

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropIndex('chat_messages_thread_read_deleted_index');
            $table->dropForeign(['deleted_by_user_id']);
            $table->dropColumn(['edited_at', 'deleted_at', 'deleted_by_user_id']);
        });
    }
};
