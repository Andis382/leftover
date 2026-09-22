<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every message sent to a customer, whether or not a provider is configured.
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 16)->default('WHATSAPP');
            $table->string('recipient', 64);
            $table->string('recipient_name')->nullable();
            $table->string('template_key', 64);
            $table->string('locale', 5);
            $table->text('body');
            $table->text('link')->nullable();
            $table->json('params')->nullable();
            $table->string('status', 16)->default('QUEUED');
            $table->string('provider', 32)->nullable();
            $table->string('provider_message_id', 128)->nullable()->index();
            $table->text('error')->nullable();
            $table->string('related_type', 64)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'created_at']);
            $table->index(['recipient', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('from_phone', 64);
            $table->text('body')->nullable();
            $table->string('kind', 16)->default('text');
            $table->string('media_id', 128)->nullable();
            $table->string('media_type', 64)->nullable();
            $table->string('provider_message_id', 128)->nullable()->unique();
            $table->boolean('handled')->default(false);
            $table->string('handled_by', 64)->nullable();
            $table->string('related_type', 64)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
            $table->index(['organization_id', 'received_at']);
        });

        Schema::create('stored_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('original_name')->nullable();
            $table->string('content_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
        Schema::dropIfExists('inbound_messages');
        Schema::dropIfExists('outbound_messages');
    }
};
