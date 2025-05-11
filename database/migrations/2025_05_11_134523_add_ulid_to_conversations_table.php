<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table): void {
            // Step 1: Add the ULID column as nullable first
            $table->ulid('ulid')->nullable()->after('id')->unique();
        });

        // Step 2: Backfill existing rows with a random ULID
        DB::table('conversations')->get()->each(function ($conversation): void {
            DB::table('conversations')
                ->where('id', $conversation->id)
                ->update(['ulid' => strtolower((string) Str::ulid())]);
        });

        // Step 3: Alter the column to be NOT NULL
        // This step cannot be done directly in SQLite. If you're using another DB (like MySQL/Postgres), you could do:
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('conversations', function (Blueprint $table): void {
                $table->ulid('ulid')->nullable(false)->change();
            });
        }
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique(['ulid']); // Drop the index first
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropColumn('ulid'); // Now drop the column
        });
    }
};
