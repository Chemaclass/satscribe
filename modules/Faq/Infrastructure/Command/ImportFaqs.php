<?php

declare(strict_types=1);

namespace Modules\Faq\Infrastructure\Command;

use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;

use function count;

final class ImportFaqs extends Command
{
    protected $signature = 'import:faqs {file : Path to the CSV file}';

    protected $description = 'Import FAQ entries from a CSV file into the faqs table';

    public function __construct(
        private readonly FaqRepositoryInterface $faqRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        LazyCollection::make(fn () => $this->readCsvLines($filePath))
            ->chunk(50)
            ->each(function (LazyCollection $chunk): void {
                $this->processChunk($chunk->all(), Carbon::now());
            });

        $this->info('FAQs imported successfully.');
        return Command::SUCCESS;
    }

    private function readCsvLines(string $filePath): Generator
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) !== count($header)) {
                $lineNumber = ftell($handle);
                $this->warn("Skipping malformed line at byte {$lineNumber}", ['line' => $line]);
                continue;
            }

            yield array_combine($header, $line);
        }

        fclose($handle);
    }

    private function processChunk($chunk, Carbon $now): void
    {
        $rows = [];
        foreach ($chunk as $row) {
            $this->processRow($row, $now, $rows);
        }
        $this->faqRepository->insertMany($rows);
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function processRow(array $row, Carbon $now, array &$rows): void
    {
        $question = $row['question'] ?? '';
        $lang = $row['lang'] ?? 'en';
        $existing = $this->faqRepository->findByQuestion($question, $lang);

        $data = [
            'answer_beginner' => $row['answer_beginner'] ?? '',
            'answer_advance' => $row['answer_advance'] ?? '',
            'answer_tldr' => $row['answer_tldr'] ?? '',
            'lang' => $lang,
            'categories' => $row['categories'] ?? '',
            'highlight' => filter_var($row['highlight'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'priority' => (int) ($row['priority'] ?? 0),
            'link' => $row['link'] ?: null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->faqRepository->update($existing->id, $data);
        } else {
            $data['question'] = $question;
            $data['created_at'] = $now;
            $rows[] = $data;
        }
    }
}
