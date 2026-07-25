<?php

declare(strict_types=1);

namespace Modules\Blockchain\Application\Blockstream;

use Modules\Blockchain\Domain\Exception\BlockchainException;

use function is_array;
use function is_bool;
use function is_int;
use function is_string;

/**
 * Blockstream answers 200 with JSON, but nothing guarantees the body matches
 * the documented shape — a proxy error page, a truncated response or an API
 * change all arrive the same way. Checking the fields this codebase relies on
 * turns that into one BlockchainException at the boundary instead of a failure
 * several layers away with no mention of the API.
 *
 * Only required fields are checked; the payloads carry more and stay unsealed.
 */
final class BlockstreamPayload
{
    public const STRING = 'string';
    public const INT = 'int';
    public const BOOL = 'bool';
    public const ARRAY = 'array';

    /**
     * @param  array<string, self::*>  $fields  field name => required type
     *
     * @return array<array-key, mixed>
     */
    public static function object(mixed $body, string $reference, array $fields): array
    {
        if (!is_array($body)) {
            throw BlockchainException::malformedPayload($reference, 'not a JSON object');
        }

        foreach ($fields as $field => $type) {
            $value = $body[$field] ?? null;

            $valid = match ($type) {
                self::STRING => is_string($value),
                self::INT => is_int($value),
                self::BOOL => is_bool($value),
                self::ARRAY => is_array($value),
            };

            if (!$valid) {
                throw BlockchainException::malformedPayload($reference, $field);
            }
        }

        return $body;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function objectList(mixed $body, string $reference): array
    {
        if (!is_array($body)) {
            throw BlockchainException::malformedPayload($reference, 'not a JSON array');
        }

        $list = [];

        foreach ($body as $index => $entry) {
            if (!is_array($entry)) {
                throw BlockchainException::malformedPayload($reference, "entry {$index}");
            }

            /** @var array<string, mixed> $entry */
            $list[] = $entry;
        }

        return $list;
    }
}
