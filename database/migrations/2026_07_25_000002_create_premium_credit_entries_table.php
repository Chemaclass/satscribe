<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A ledger rather than a balance column: every grant and every spend is a row,
 * and the balance is their sum. Money the visitor actually paid is involved, so
 * "I paid and got nothing" has to be answerable from the data, and a running
 * total cannot drift out of step with the payments that produced it.
 */
return new class() extends Migration {
    public function up(): void
    {
        Schema::create('premium_credit_entries', function (Blueprint $table): void {
            // Credit belongs to a Nostr identity, not to a tracking id: the
            // latter is hash(ip + user-agent) and would strand a paid balance
            // the moment someone changed network.
            $table->id();
            $table->string('npub', 64)->index();
            $table->integer('delta');
            $table->string('reason', 32);
            $table->string('payment_hash')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_credit_entries');
    }
};
