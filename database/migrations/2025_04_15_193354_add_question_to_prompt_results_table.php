<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_results', function (Blueprint $table): void {
            $table->text('question')->nullable()->after('input');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_results', function (Blueprint $table): void {
            $table->dropColumn('question');
        });
    }
};
