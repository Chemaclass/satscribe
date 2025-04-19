<?php
declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class FaqRepository
{
    public function findByQuestion(string $question): ?object
    {
        return DB::table('faqs')->where('question', $question)->first();
    }

    public function update(int $id, array $data): void
    {
        DB::table('faqs')->where('id', $id)->update($data);
    }

    public function insertMany(array $rows): void
    {
        DB::table('faqs')->insert($rows);
    }
}
