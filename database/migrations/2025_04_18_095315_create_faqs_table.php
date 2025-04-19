<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer_beginner');
            $table->text('answer_advance');
            $table->text('answer_tldr');
            $table->string('categories')->default('unknown');
            $table->boolean('highlight')->default(false);
            $table->integer('priority')->default(100); // lower = higher priority
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
