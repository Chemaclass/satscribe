<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final class ImportFaqs extends Command
{
    protected $signature = 'faqs:import {file : Path to the CSV file}';

    protected $description = 'Import FAQ entries from a CSV file into the faqs table';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        LazyCollection::make(function () use ($filePath) {
            $handle = fopen($filePath, 'r');
            while (($line = fgetcsv($handle)) !== false) {
                yield $line;
            }
            fclose($handle);
        })
            ->skip(1) // Skip header
            ->chunk(100)
            ->each(function ($chunk) {
                $now = Carbon::now();
                $rows = [];

                foreach ($chunk as $row) {
                    [
                        $question,
                        $answer_beginner,
                        $answer_advance,
                        $answer_tldr,
                        $categories,
                        $highlight,
                        $priority,
                        $link
                    ] = $row;

                    if (DB::table('faqs')->where('question', $question)->exists()) {
                        continue; // skip duplicate
                    }

                    $rows[] = [
                        'question' => $question,
                        'answer_beginner' => $answer_beginner,
                        'answer_advance' => $answer_advance,
                        'answer_tldr' => $answer_tldr,
                        'categories' => $categories,
                        'highlight' => filter_var($highlight, FILTER_VALIDATE_BOOLEAN),
                        'priority' => (int) $priority,
                        'link' => $link ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('faqs')->insert($rows);
            });

        $this->info('FAQs imported successfully.');
        return Command::SUCCESS;
    }
}
