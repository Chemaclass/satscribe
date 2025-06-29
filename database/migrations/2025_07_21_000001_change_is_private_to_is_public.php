<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('chats', static function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('tracking_id');
        });

        DB::table('chats')->update([
            'is_public' => DB::raw('NOT is_private'),
        ]);

        Schema::table('chats', static function (Blueprint $table): void {
            $table->dropColumn('is_private');
        });
    }

    public function down(): void
    {
        Schema::table('chats', static function (Blueprint $table): void {
            $table->boolean('is_private')->default(false)->after('tracking_id');
        });

        DB::table('chats')->update([
            'is_private' => DB::raw('NOT is_public'),
        ]);

        Schema::table('chats', static function (Blueprint $table): void {
            $table->dropColumn('is_public');
        });
    }
};
