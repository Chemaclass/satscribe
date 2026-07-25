# Changelog

All notable changes to this project are documented here, following
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

Satscribe deploys continuously: every push to `main` ships to production, so
there are no version tags. Entries are grouped by the date they went live.
Anything older than the first section below lives in `git log`.

## [Unreleased]

### Security

- **A message's stored payload was readable by anyone.** `history/{id}/raw` took a
  bare integer and returned that message's data with no ownership check, so a
  private chat's contents were reachable by counting upwards. It now applies the
  same visibility rule as opening the chat.
- **The UTXO trace endpoint had no depth limit.** Each level walks every input of
  every transaction above it, so `?depth=500` on one unauthenticated request
  could pin a worker for its full 300s limit while fanning out to Blockstream.
  Clamped to 5; the page only ever asks for 2.
- **No `/api/*` route was rate limited.** Laravel 11 dropped the default API
  throttle and it was never opted back in. Limited to 120 requests a minute,
  which leaves roughly threefold headroom over the paywall modal's polling.

### Fixed

- **Two of the three OpenRouter models on offer no longer existed.** Both free
  entries (`meta-llama/llama-3.3-70b-instruct:free`,
  `deepseek/deepseek-chat-v3-0324:free`) had been withdrawn upstream, so picking
  either one failed. Every model id is now checked against the provider's own
  catalogue.
- The Nostr login did not rotate the session id, leaving the app open to session
  fixation. Rotated on login and logout.
- A signed Alby webhook with an unusable body was reported as a signature
  failure, and answered 5xx so Alby retried it forever. It is now a payload
  error answered with 422.
- The model picker opened downward even when there was no room, cutting off the
  API key field. It now flips upward when the space below is too small.
- **A mempool transaction was cached forever.** The force-refresh flag is stored
  on the assistant message but was read from the first message — always the
  user's — so it evaluated false for every chat. A transaction answered while
  unconfirmed kept replaying that answer after it had been mined.
- The answer cache returned whichever matching row the database happened to
  yield rather than the newest, so the first answer ever generated for a
  question was served indefinitely.
- **UTXO traces computed before the vout fix were served forever.** The table is
  a cache with no expiry, so rows recording `vout: null` for every output were
  never replaced. Stored traces now carry a version and a stale row is
  recomputed on next request.
- **The block tip height was fetched from Blockstream on every page view.** Only
  the derived maximum read the cache; the current height itself never did, so
  each home and chat render made its own uncached outbound call. Now cached for
  the same ten minutes.
- A chat with no messages returned 500 instead of a not found.
- Unsharing a chat reported it as shared: the endpoint answered a constant
  `true` whatever it stored, and read the flag with a cast that turns the string
  `"false"` into `true`.

### Changed

- **PHPStan raised to level 9**, its maximum, across `app`, `modules` and
  `tests`. Getting there was mostly boundary validation rather than annotation:
  the analyser kept pointing at real places where untyped external data was read
  as though its shape were known.
- Refreshed the model catalogue: Groq gains GPT-OSS 120B/20B and Kimi K2,
  OpenRouter moves to current free models, and OpenAI gains the cheaper
  GPT-5 nano / GPT-4.1 nano tiers.
- Providers with a free tier are listed first, free models before paid ones, and
  paid models cheapest-first — so the least expensive option is always nearest
  the top.

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
