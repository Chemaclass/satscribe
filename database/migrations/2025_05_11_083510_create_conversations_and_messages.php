<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // 3. Migrate satscribe_descriptions data into conversations + messages
        $descriptions = DB::table('satscribe_descriptions')->get();

        foreach ($descriptions as $desc) {
            $conversationId = DB::table('conversations')->insertGetId([
                'title' => ucfirst($desc->type) . ':' . $desc->input,
                'created_at' => $desc->created_at,
                'updated_at' => $desc->updated_at,
            ]);

            // Insert user message (simulate question)
            DB::table('messages')->insert([
                'conversation_id' => $conversationId,
                'role' => 'user',
                'content' => $desc->question ?? '',
                'meta' => json_encode([
                    'type' => $desc->type,
                    'input' => $desc->input,
                    'persona' => $desc->persona,
                ]),
                'created_at' => $desc->created_at,
                'updated_at' => $desc->updated_at,
            ]);

            // Insert assistant message (AI result)
            DB::table('messages')->insert([
                'conversation_id' => $conversationId,
                'role' => 'assistant',
                'content' => $desc->ai_response,
                'meta' => json_encode([
                    'type' => $desc->type,
                    'input' => $desc->input,
                    'persona' => $desc->persona,
                    'raw_data' => $desc->raw_data,
                    'force_refresh' => $desc->force_refresh,
                ]),
                'created_at' => $desc->created_at,
                'updated_at' => $desc->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // Restore satscribe_descriptions from conversations/messages

        // Check if the original table exists before rolling back data
        if (Schema::hasTable('satscribe_descriptions')) {
            // Remove rows to prevent duplicates
            DB::table('satscribe_descriptions')->truncate();

            // For each conversation, try to reconstruct satscribe_description entries
            $query = "
                SELECT c.id as conversation_id,
                       u.content as question,
                       a.content as ai_response,
                       u.meta as user_meta,
                       a.meta as assistant_meta,
                       c.created_at,
                       c.updated_at
                FROM conversations c
                JOIN messages u ON u.conversation_id = c.id AND u.role = 'user'
                JOIN messages a ON a.conversation_id = c.id AND a.role = 'assistant'
            ";

            $rows = DB::select($query);

            foreach ($rows as $row) {
                // Decode meta data
                $userMeta = json_decode($row->user_meta ?? '{}', true);
                $assistantMeta = json_decode($row->assistant_meta ?? '{}', true);

                DB::table('satscribe_descriptions')->insert([
                    'type'          => $userMeta['type'] ?? null,
                    'input'         => $userMeta['input'] ?? null,
                    'persona'       => $userMeta['persona'] ?? null,
                    'question'      => $row->question ?? '',
                    'ai_response'   => $row->ai_response ?? '',
                    'raw_data'      => $assistantMeta['raw_data'] ?? null,
                    'force_refresh' => $assistantMeta['force_refresh'] ?? 0,
                    'created_at'    => $row->created_at,
                    'updated_at'    => $row->updated_at,
                ]);
            }
        }

        // Drop new tables
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
