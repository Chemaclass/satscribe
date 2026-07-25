<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * utxo_traces is a permanent cache with no expiry, so a trace computed by an
 * older version of the tracer is served forever. Rows written before the vout
 * index fix record `vout: null` for every output, and nothing would ever have
 * replaced them.
 *
 * Existing rows default to version 0 and so no longer match a lookup; the next
 * request for that txid recomputes and overwrites the row through the unique
 * (txid, depth) key, which means nothing has to be deleted here.
 */
return new class() extends Migration {
    public function up(): void
    {
        Schema::table('utxo_traces', function (Blueprint $table): void {
            $table->unsignedInteger('version')->default(0)->after('depth');
        });
    }

    public function down(): void
    {
        Schema::table('utxo_traces', function (Blueprint $table): void {
            $table->dropColumn('version');
        });
    }
};
