<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('conversations', 'chats');

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['conversation_id']);
            $table->renameColumn('conversation_id', 'chat_id');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign('chat_id')->references('id')->on('chats')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['chat_id']);
            $table->renameColumn('chat_id', 'conversation_id');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });

        Schema::rename('chats', 'conversations');
    }
};
