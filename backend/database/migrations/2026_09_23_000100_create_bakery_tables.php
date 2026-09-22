<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // When the morning bake plan is made and sent, in the organisation's time zone.
            $table->time('plan_time')->default('04:00');
            // Minutes after closing before "count what's left" goes out if nobody counted.
            $table->unsignedSmallInteger('count_reminder_offset')->default(15);
            // Who receives the bake plan on WhatsApp (usually the baker).
            $table->string('plan_phone', 40)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            // Used for the closing reminder on WhatsApp.
            $table->string('phone', 40)->nullable();
        });

        Schema::create('shop_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO: 1 = Monday … 7 = Sunday
            $table->time('opens_at')->nullable(); // both null: closed that day
            $table->time('closes_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'weekday']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('category', 16);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('unit_cost_cents')->nullable();
            $table->unsignedSmallInteger('tray_size')->default(1);
            $table->json('baselines'); // the usual quantity for each weekday, Monday first
            $table->json('active_weekdays'); // ISO weekdays it is baked on
            $table->unsignedInteger('shelf_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'shelf_order']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable(); // null: made by the morning job
            $table->foreignId('outbound_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('baked_confirmed_at')->nullable();
            $table->foreignId('baked_confirmed_by')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'date']);
        });

        Schema::create('daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('planned_qty')->nullable(); // the suggestion at plan time
            $table->json('forecast')->nullable(); // why: reason + confidence at plan time
            $table->unsignedInteger('baked_qty')->nullable(); // null: baked as planned
            $table->unsignedInteger('left_qty')->nullable(); // null: not counted
            $table->time('sold_out_at')->nullable();
            $table->foreignId('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'product_id', 'date']);
            $table->index(['organization_id', 'date']);
        });

        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 16); // OPEN, COUNTED, SKIPPED
            $table->string('skip_reason', 200)->nullable();
            $table->foreignId('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
        Schema::dropIfExists('daily_records');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('products');
        Schema::dropIfExists('shop_hours');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('phone'));
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn(['plan_time', 'count_reminder_offset', 'plan_phone']));
    }
};
