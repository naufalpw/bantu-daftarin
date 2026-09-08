<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_thread_user_states', function (Blueprint $table): void {
            $table->uuid('chat_email_pending_token')->nullable();
            $table->timestamp('chat_email_pending_at')->nullable();
            $table->timestamp('chat_email_last_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chat_thread_user_states', function (Blueprint $table): void {
            $table->dropColumn([
                'chat_email_pending_token',
                'chat_email_pending_at',
                'chat_email_last_sent_at',
            ]);
        });
    }
};
