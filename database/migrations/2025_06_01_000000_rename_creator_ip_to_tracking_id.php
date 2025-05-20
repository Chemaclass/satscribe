<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->renameColumn('creator_ip', 'tracking_id');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            $table->renameColumn('tracking_id','creator_ip');
        });
    }
};
