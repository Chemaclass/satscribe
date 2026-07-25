---
description: External API failures, domain exceptions, streaming errors, user-facing messages
globs:
  - "modules/*/Infrastructure/**/*.php"
  - "modules/*/Application/**/*.php"
  - "modules/*/Domain/Exception/**/*.php"
---

# Error Handling

Satscribe depends on three external services — Blockstream, OpenAI, Alby. **Network failure is expected, not exceptional.** Every outbound call is wrapped.

## External calls

```php
try {
    $response = $this->httpClient->get($url);
} catch (ConnectionException|RequestException $e) {
    $this->logger->warning('blockstream fetch failed', ['url' => $url, 'error' => $e->getMessage()]);

    throw BlockchainUnavailable::forEndpoint($url, $e);
}
```

Rules:

- Catch the **specific** transport exception, never `\Throwable`
- Translate it into a Domain exception at the Infrastructure boundary — Application code never sees an `Illuminate\Http\Client` type
- Log with context (endpoint, identifier), never the full response body or any key
- Never swallow silently. A caught-and-ignored exception is a bug report you'll never receive

## Domain exceptions

Live in `modules/<Module>/Domain/` — pure PHP, no Laravel.

| Pattern | Base | Static factory |
|---|---|---|
| `<Thing>NotFound` | `RuntimeException` | `withId()`, `forTxid()` |
| `Invalid<Thing>` | `DomainException` | `empty()`, `withFormat()` |
| `<Service>Unavailable` | `RuntimeException` | `forEndpoint()` |

Use a static factory so the message lives with the exception, not scattered at throw sites. Never throw bare `\Exception` or `\RuntimeException`.

## Streaming

`CreateChatStreamAction` / `AddMessageStreamAction` write incrementally. A failure mid-stream cannot become an HTTP error code — headers are already sent.

- Handle failures **inside** the stream and emit a terminal error event the frontend can render
- Never let an exception escape a stream generator — it produces a silently truncated reply
- Log stream failures explicitly; they're invisible in HTTP status metrics

## User-facing messages

- Never surface raw exception messages, stack traces, or upstream API errors to users
- Blockchain lookups that find nothing are a **normal empty result**, not an error — a nonexistent txid gets "not found", not a 500
- Rate limiting (`modules/Shared/`) returns 429 with a clear retry message

## Never

- `catch (\Throwable $e) {}` — empty or log-only catch of everything
- Returning `null` to signal failure where an exception belongs
- `report()`-and-continue on a call whose result the next line needs
- Leaking API keys or npubs into logs or error messages
