<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prompt_results', function (Blueprint $table): void {
            $table->boolean('force_refresh')->default(false)->after('raw_data');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_results', function (Blueprint $table): void {
            $table->dropColumn('force_refresh');
        });
    }
};
