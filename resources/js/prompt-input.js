/**
 * Client-side mirror of Modules\Shared\Domain\Data\Chat\PromptInput::fromRaw().
 *
 * The server decides "block vs transaction" with
 *   is_numeric($input) || preg_match('/^0{8,}[a-f0-9]{56}$/i', $input)
 * and every place the UI labels an input has to reach the same verdict, or the
 * chat header ends up claiming "Block #abc…" for something the backend fetched
 * as a transaction. Keep this file the only client-side copy of that rule.
 */

// PHP's is_numeric(): optional sign, decimals and exponent, no hex, no bare '.'.
const NUMERIC = /^[+-]?(\d+(\.\d*)?|\.\d+)(e[+-]?\d+)?$/i;

// Same expression as the PHP side: 8+ leading zeros then 56 hex chars.
const BLOCK_HASH = /^0{8,}[a-f0-9]{56}$/i;

const HEX_64 = /^[a-f0-9]{64}$/i;

const BLOCK_HEIGHT = /^\d+$/;

const normalize = (raw) => String(raw ?? '').trim();

/** The server's block-vs-transaction verdict. */
export function isBlockInput(raw) {
    const value = normalize(raw);

    return NUMERIC.test(value) || BLOCK_HASH.test(value);
}

/**
 * Richer classification for the search field: which *kind* of block, and
 * whether the value is submittable at all.
 *
 * @returns {{kind: 'empty'|'invalid'|'block-height'|'block-hash'|'transaction', value: string, valid: boolean, isBlock: boolean, isHex64: boolean, isBlockHeight: boolean, isBlockHash: boolean}}
 */
export function classifyPromptInput(raw, maxBlockHeight = Number.MAX_SAFE_INTEGER) {
    const value = normalize(raw);

    const isHex64 = HEX_64.test(value);
    const isBlockHash = BLOCK_HASH.test(value);
    const isBlockHeight = BLOCK_HEIGHT.test(value) && Number(value) <= maxBlockHeight;
    const valid = isHex64 || isBlockHeight || isBlockHash;

    let kind = 'invalid';
    if (value === '') {
        kind = 'empty';
    } else if (valid) {
        kind = isBlockHash ? 'block-hash' : (isBlockHeight ? 'block-height' : 'transaction');
    }

    return {
        kind,
        value,
        valid,
        isBlock: isBlockInput(value),
        isHex64,
        isBlockHeight,
        isBlockHash,
    };
}

/** Lucide icon name matching the detected kind. */
export function promptInputIcon(raw) {
    return isBlockInput(raw) ? 'box' : 'arrow-right-left';
}

/**
 * Short label for the chat header. Mirrors the Blade helper in
 * partials/chat.blade.php — change both together.
 */
export function promptInputLabel(raw, labels = {}) {
    const value = normalize(raw);
    const blockLabel = labels.block ?? 'Block';
    const transactionLabel = labels.transaction ?? 'Transaction';

    if (!isBlockInput(value)) {
        return `${transactionLabel} ${value.slice(0, 12)}...`;
    }

    // A block *hash* is 64 chars — "Block #0000000000…" reads as nonsense, so
    // only heights get the # prefix.
    return BLOCK_HEIGHT.test(value)
        ? `${blockLabel} #${value}`
        : `${blockLabel} ${value.slice(0, 12)}...`;
}
