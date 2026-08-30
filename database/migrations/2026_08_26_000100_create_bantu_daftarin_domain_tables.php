<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('email')->unique();
            $table->string('role', 32)->default('SUPER_ADMIN');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auth_challenges', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('code_hash');
            $table->dateTime('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->ipAddress('request_ip')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type', 'used_at']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 24)->index();
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_requirements', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->boolean('is_required')->default(true);
            $table->json('allowed_extensions');
            $table->json('allowed_mimes');
            $table->unsignedBigInteger('max_size_bytes');
            $table->string('condition')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['service_id', 'code']);
            $table->index(['service_id', 'active']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status', 40)->index();
            $table->decimal('price_amount_snapshot', 12, 2);
            $table->char('currency', 3);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('estimated_completion_at')->nullable();
            $table->timestamp('documents_accepted_at')->nullable();
            $table->timestamp('external_process_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['service_id', 'status']);
        });

        Schema::create('personal_application_details', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('marital_status', 64)->nullable();
            $table->string('family_status', 64)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->string('gender', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('business_application_details', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->string('business_name');
            $table->string('business_type', 128)->nullable();
            $table->string('purpose', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('business_representatives', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 40);
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['application_id', 'is_primary']);
        });

        Schema::create('application_requirements', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('service_requirement_id')->nullable()->constrained('service_requirements')->nullOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->boolean('is_required')->default(true);
            $table->json('allowed_extensions');
            $table->json('allowed_mimes');
            $table->unsignedBigInteger('max_size_bytes');
            $table->string('condition_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->string('status', 32)->default('PENDING')->index();
            $table->timestamps();
            $table->unique(['application_id', 'code']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('application_requirement_id')->nullable()->constrained('application_requirements')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->text('original_filename');
            $table->string('stored_filename');
            $table->string('storage_disk', 32);
            $table->string('storage_path');
            $table->string('mime_type', 128);
            $table->string('extension', 12);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256_checksum', 64);
            $table->string('scan_status', 24)->index();
            $table->string('review_status', 32)->index();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('uploaded_at');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('revision_instruction')->nullable();
            $table->timestamp('retention_until')->nullable();
            $table->timestamp('deletion_scheduled_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('deletion_reason')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->index(['application_id', 'review_status']);
            $table->index(['application_requirement_id', 'version_number']);
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->foreignId('reviewer_admin_id')->constrained('admins')->restrictOnDelete();
            $table->string('action', 32);
            $table->text('reason')->nullable();
            $table->text('instruction')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'created_at']);
        });

        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->string('actor_type', 32);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 32);
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['document_id', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('provider', 32)->default('xendit');
            $table->string('external_id')->nullable()->unique();
            $table->string('reference_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 32)->index();
            $table->text('checkout_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'status']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->string('provider', 32);
            $table->string('event_id');
            $table->string('event_type', 128)->nullable();
            $table->json('payload');
            $table->dateTime('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('status', 24)->default('RECEIVED')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id']);
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('actor_type', 32);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at']);
        });

        Schema::create('application_estimate_histories', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('admins')->restrictOnDelete();
            $table->timestamp('previous_estimated_completion_at')->nullable();
            $table->dateTime('new_estimated_completion_at');
            $table->text('reason');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at']);
        });

        Schema::create('application_consents', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('consent_type', 64);
            $table->string('version', 32);
            $table->foreignId('accepted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('accepted_at');
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->unique(['application_id', 'consent_type']);
        });

        Schema::create('chat_threads', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->unique()->constrained('applications')->restrictOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('chat_thread_id')->constrained('chat_threads')->restrictOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['chat_thread_id', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 128);
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at', 'created_at']);
        });

        Schema::create('result_documents', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_id')->constrained('applications')->restrictOnDelete();
            $table->string('type', 32);
            $table->text('original_filename');
            $table->string('stored_filename');
            $table->string('storage_disk', 32);
            $table->string('storage_path');
            $table->string('mime_type', 128);
            $table->string('extension', 12);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256_checksum', 64);
            $table->string('scan_status', 24)->index();
            $table->string('verification_status', 24)->index();
            $table->foreignId('uploaded_by_admin_id')->constrained('admins')->restrictOnDelete();
            $table->dateTime('uploaded_at');
            $table->foreignId('verified_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('retention_until')->nullable();
            $table->timestamp('deletion_scheduled_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('deletion_reason')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'type', 'verification_status']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');
            $table->id();
            $table->string('event', 128)->index();
            $table->string('actor_type', 32)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('auditable_type', 128)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('properties')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('result_documents');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
        Schema::dropIfExists('application_consents');
        Schema::dropIfExists('application_estimate_histories');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('document_reviews');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('application_requirements');
        Schema::dropIfExists('business_representatives');
        Schema::dropIfExists('business_application_details');
        Schema::dropIfExists('personal_application_details');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('service_requirements');
        Schema::dropIfExists('services');
        Schema::dropIfExists('auth_challenges');
        Schema::dropIfExists('admins');
    }
};
