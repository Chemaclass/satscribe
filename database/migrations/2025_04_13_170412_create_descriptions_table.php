<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'block' or 'transaction'
            $table->string('input'); // block height or tx hash
            $table->text('description'); // AI-generated paragraph
            $table->json('raw_data'); // optional, stores the raw JSON from the API
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descriptions');
    }
};
