<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('utxo_traces', function (Blueprint $table): void {
            $table->id();
            $table->string('txid');
            $table->unsignedInteger('depth');
            $table->json('result');
            $table->timestamps();

            $table->unique(['txid', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utxo_traces');
    }
};
