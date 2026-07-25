<?php

declare(strict_types=1);

namespace Modules\Faq\Infrastructure\Command;

use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\LazyCollection;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use RuntimeException;
use stdClass;

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

        // The generator is passed directly rather than wrapped in a closure:
        // LazyCollection::make() declares the closure form as returning void,
        // which no generator satisfies.
        LazyCollection::make($this->readCsvLines($filePath))
            ->chunk(50)
            ->each(function (LazyCollection $chunk): void {
                $this->processChunk($chunk->all(), Carbon::now());
            });

        $this->info('FAQs imported successfully.');
        return Command::SUCCESS;
    }

    /**
     * @return Generator<int, array<string, string|null>>
     */
    private function readCsvLines(string $filePath): Generator
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open {$filePath} for reading.");
        }

        $rawHeader = fgetcsv($handle);
        if ($rawHeader === false) {
            fclose($handle);
            throw new RuntimeException("{$filePath} has no CSV header row.");
        }

        $header = array_map(static fn ($column) => (string) $column, $rawHeader);

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) !== count($header)) {
                $lineNumber = ftell($handle);
                $columns = implode(',', array_map(static fn ($value) => (string) $value, $line));
                $this->warn("Skipping malformed line at byte {$lineNumber}: {$columns}");
                continue;
            }

            yield array_combine($header, $line);
        }

        fclose($handle);
    }

    /**
     * @param  array<int, array<string, string|null>>  $chunk
     */
    private function processChunk(array $chunk, Carbon $now): void
    {
        $rows = [];
        foreach ($chunk as $row) {
            $this->processRow($row, $now, $rows);
        }
        $this->faqRepository->insertMany(array_values($rows));
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

        if ($existing instanceof stdClass) {
            $this->faqRepository->update($existing->id, $data);
        } else {
            $data['question'] = $question;
            $data['created_at'] = $now;
            $rows[] = $data;
        }
    }
}
