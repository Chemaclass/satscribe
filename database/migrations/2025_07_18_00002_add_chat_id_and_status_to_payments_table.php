<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->foreignId('chat_id')->nullable()
                ->after('tracking_id')
                ->constrained('chats')
                ->onDelete('cascade');

            $table->string('status')->default('SETTLED');
            $table->string('failure_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->dropForeign(['chat_id']);
            $table->dropColumn(['chat_id', 'status', 'failure_reason']);
        });
    }
};
