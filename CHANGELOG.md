# Changelog

All notable changes to this project are documented here, following
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

Satscribe deploys continuously: every push to `main` ships to production, so
there are no version tags. Entries are grouped by the date they went live.
Anything older than the first section below lives in `git log`.

## [Unreleased]

Nothing yet.

## 2026-07-25

### Added

- Selectable AI provider and model. Alongside OpenAI, the app now speaks to
  **Groq** and **OpenRouter**, both of which offer free-tier models. Visitors can
  also supply their own API key, which is held in `localStorage` and sent as an
  `X-Ai-Api-Key` header — never logged, never persisted server-side.
- Free models are the automatic default. When `GROQ_API_KEY` or
  `OPENROUTER_API_KEY` is configured, chats use that provider's free model
  instead of billing OpenAI.
- Blockstream payload validation (`BlockstreamPayload`), so a 200 carrying an
  error page or a truncated body raises a `BlockchainException` at the boundary
  rather than failing several layers away.
- Test coverage for the streaming chat path, the Lightning paywall, and the Alby
  webhook payload parser — all three previously had none.

### Fixed

- **Chats appeared to hang forever.** A provider that answered 200 but produced
  no usable content had its empty answer saved and reported as a success, so the
  page showed loading skeletons that never resolved. The stream now fails with a
  visible error and no chat is written.
- **Provider failures were unexplained.** A rejected request reported only
  "request failed"; it now names the cause — bad key, unavailable model,
  exhausted quota or provider outage — without revealing anything about the key.
- **Any visitor could trigger a 500.** Request fields cast with `(string)` threw
  `Array to string conversion` when sent as arrays, so `?q[]=x` was enough to
  break `/api/prefetch`, `/faq` and `/auth/nostr/login`.
- **`?lang=` was unvalidated** and reached `app()->setLocale()`, which Laravel
  expands into a translation file path. Locales are now restricted to those the
  app ships.
- **A CoinGecko outage took the whole site down.** Prices are shared from a
  service provider's `boot()`, which runs on every request, so an unguarded
  failure there returned 500 on every page.
- Malformed upstream data no longer crashes requests: transaction summaries,
  UTXO traces, cached invoices, Alby webhook bodies and coinbase scripts all
  validate types before use instead of assuming them.
- Transaction summary totals silently produced wrong numbers when an input or
  output was malformed — figures that are fed verbatim into the AI prompt.
- The UTXO transaction cache was a method-level `static`, shared across every
  instance for the life of the process, and grew unbounded in queue workers.
- Lightning invoices were re-minted on every paywalled request whenever the
  configured expiry was at or below the caching margin.
- The daily quota is enforced inside the streaming generator, so exhausting it
  used to tear down the stream with no terminal event.

### Changed

- PHPStan raised from **level 1 to level 8** across `app`, `modules` and `tests`.
- `Shared` no longer imports from `Payment`: the paywall invoice is obtained
  through a `Shared`-owned port that `Payment` implements, removing the last
  module dependency cycle.
- The default `OPENAI_MODEL` is `gpt-4o-mini`. The previous default, `gpt-4`,
  was not in the app's own model allowlist and is not enabled on every account.

### Removed

- Dead code: the empty `ChatFacade` stub, the unused `InvoiceData::create()`, and
  assorted unreachable branches.
