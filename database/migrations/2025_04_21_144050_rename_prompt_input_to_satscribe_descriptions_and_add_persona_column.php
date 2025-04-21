<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('prompt_results', 'satscribe_descriptions');

        Schema::table('satscribe_descriptions', function (Blueprint $table): void {
            $table->string('persona')->nullable()->after('question');
        });
    }

    public function down(): void
    {
        Schema::table('satscribe_descriptions', function (Blueprint $table): void {
            $table->dropColumn('persona');
        });

        Schema::rename('satscribe_descriptions', 'prompt_results');
    }
};
