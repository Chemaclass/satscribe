<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->string('status')->default('SETTLED');
            $table->string('failure_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->dropColumn(['status', 'failure_reason']);
        });
    }
};
