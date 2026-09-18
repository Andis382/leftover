<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The whole schema. Five tables, and the interesting one is `counts`.
 *
 * `counts` records two numbers and a time: how many were baked, how many were
 * still there at closing, and — if the shelf emptied — when. That third column
 * is the one nobody keeps. A sell-out looks like a perfect day in a till
 * report, because a sale that never happened leaves no record anywhere. It is
 * the only way to know that demand was higher than production, so it is stored
 * as a first-class fact rather than inferred.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('shop_name')->nullable()->after('name');
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('locale', 5)->default('sq');
            $table->string('currency', 3)->default('EUR');
            $table->string('timezone')->default('Europe/Tirane');

            // When the shop opens and shuts. Used to judge how early a sell-out
            // was, which is the difference between "a little short" and "we
            // were turning people away for five hours".
            $table->time('opens_at')->default('06:00');
            $table->time('closes_at')->default('20:00');

            // When tomorrow's plan is written. Before the baker gets up.
            $table->time('plan_at')->default('04:00');

            $table->string('notify_driver')->default('none');
            $table->string('telegram_chat_id')->nullable();
            $table->timestamp('onboarded_at')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit')->default('piece');       // piece | kg | tray
            $table->string('category')->nullable();          // bread | pastry | cake | savoury
            $table->decimal('price', 8, 2)->nullable();      // one unit, for putting waste in money
            $table->decimal('cost', 8, 2)->nullable();       // what it cost to make, if known
            $table->unsignedSmallInteger('typical_batch')->nullable();
            $table->unsignedSmallInteger('round_to')->default(1);   // trays of 12, etc.
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'active', 'sort']);
        });

        // One row per shop per day. It exists even when nothing was counted,
        // so that a skipped day is a recorded fact rather than a hole.
        Schema::create('day_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('on_date');
            $table->unsignedTinyInteger('weekday');          // 1 Monday … 7 Sunday
            $table->string('status')->default('open');        // open | counted | skipped | shut
            $table->text('note')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'on_date']);
            $table->index(['user_id', 'weekday', 'on_date']);
        });

        Schema::create('counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_sheet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('baked_qty');
            $table->unsignedSmallInteger('left_qty')->default(0);

            // Null means it never sold out. A time means the shelf was empty
            // from then on, and everything after it is demand nobody served.
            $table->time('sold_out_at')->nullable();

            $table->timestamps();

            $table->unique(['day_sheet_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('for_date');
            $table->unsignedTinyInteger('weekday');
            $table->string('status')->default('draft');       // draft | sent | read
            $table->text('body')->nullable();                 // exactly what was sent, kept verbatim
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'for_date']);
        });

        Schema::create('plan_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('suggested_qty')->nullable();
            $table->unsignedSmallInteger('previous_qty')->nullable();
            $table->smallInteger('delta')->default(0);

            // How many counted days this line stands on, and what happened on
            // them. Printed next to the suggestion: a number built on two
            // Tuesdays should not be read like a number built on six.
            $table->unsignedTinyInteger('observations')->default(0);
            $table->string('confidence')->default('none');    // none | low | fair | good
            $table->string('reason')->nullable();             // a key in lang/*/plan.php
            $table->decimal('avg_left', 6, 2)->nullable();
            $table->unsignedTinyInteger('sold_out_days')->default(0);
            $table->timestamps();

            $table->unique(['plan_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_lines');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('counts');
        Schema::dropIfExists('day_sheets');
        Schema::dropIfExists('products');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'shop_name', 'city', 'phone', 'locale', 'currency', 'timezone',
                'opens_at', 'closes_at', 'plan_at', 'notify_driver',
                'telegram_chat_id', 'onboarded_at',
            ]);
        });
    }
};
