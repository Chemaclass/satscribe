<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Command;

use App\Models\FlaggedWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Chat\Domain\Enum\FlaggedWordSeverity;
use RuntimeException;

final class ImportFlaggedWords extends Command
{
    protected $signature = 'import:flagged-words {path : Path to the CSV file}';

    protected $description = 'Import a list of flagged words from a CSV file';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!$this->fileExists($path)) {
            return self::FAILURE;
        }

        $lines = $this->readLines($path);
        $inserted = $this->importLines($lines);

        $this->info("✅ Import completed. {$inserted} word(s) added.");
        return self::SUCCESS;
    }

    private function fileExists(string $path): bool
    {
        if (!File::exists($path)) {
            $this->error("The file at path '{$path}' does not exist.");
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function readLines(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read {$path}.");
        }

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     */
    private function importLines(array $lines): int
    {
        $inserted = 0;

        foreach ($lines as $line) {
            $wordData = $this->parseLine($line);

            if (!$wordData) {
                continue;
            }

            [$word, $severity, $isActive] = $wordData;

            if ($this->wordExists($word)) {
                $this->line("Skipped (already exists): {$word}");
                continue;
            }

            FlaggedWord::create([
                'word' => $word,
                'severity' => $severity,
                'is_active' => $isActive,
            ]);

            $this->info("Imported: {$word} (severity: {$severity->value}, active: " . ($isActive ? 'yes' : 'no') . ')');
            ++$inserted;
        }

        return $inserted;
    }

    /**
     * @return array{string, FlaggedWordSeverity, bool}|null null for an unusable line
     */
    private function parseLine(string $line): ?array
    {
        $columns = array_map(static fn ($column) => trim((string) $column), str_getcsv($line));
        $word = $columns[0];

        if (!$word) {
            $this->warn("Skipped invalid line: '{$line}'");
            return null;
        }

        $severity = FlaggedWordSeverity::tryFrom($columns[1] ?? '') ?? FlaggedWordSeverity::MEDIUM;
        if (!isset($columns[1]) || !FlaggedWordSeverity::tryFrom($columns[1])) {
            $this->warn("Invalid or missing severity for '{$word}', defaulting to 'medium'");
        }

        $isActive = isset($columns[2]) ? filter_var($columns[2], FILTER_VALIDATE_BOOLEAN) : true;

        return [$word, $severity, $isActive];
    }

    private function wordExists(string $word): bool
    {
        return FlaggedWord::where('word', $word)->exists();
    }
}
