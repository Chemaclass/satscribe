# Modular Architecture

All business logic lives in [`modules/`](../modules). `app/` holds only Laravel
glue — Eloquent models and framework wiring. Every module has the same shape:

```
<Module>
├── Application/                 # Actions, services, the module facade
├── Domain/                      # Interfaces, enums, value objects, DTOs, exceptions
├── Infrastructure/              # Controllers, repositories, middleware, commands
└── <Module>ServiceProvider.php
```

## Layer contract

| Layer | Contains | May depend on | Must never contain |
|---|---|---|---|
| **Domain** | Interfaces, enums, value objects, DTOs, exceptions | Nothing | Infrastructure imports, facades |
| **Application** | Actions, services, facades | Domain | Controllers, HTTP, DB queries |
| **Infrastructure** | Controllers, repositories, middleware, commands | Application, Domain | Business logic |

Dependencies point one way: **Infrastructure → Application → Domain**.

## Modules

| Module | Responsibility |
|---|---|
| `Blockchain` | Blockstream and CoinGecko lookups — blocks, transactions, prices |
| `Chat` | Chat creation, streaming replies, history, flagged words |
| `OpenAI` | Provider registry and chat-completions calls (OpenAI, Groq, OpenRouter) |
| `Nostr` | Decentralised auth — no passwords |
| `Payment` | Lightning invoices via Alby |
| `UtxoTrace` | UTXO tracing across transaction inputs |
| `Faq` | FAQ pages |
| `Shared` | Cross-cutting helpers, rate limiting, middleware |

## Rules that keep the boundaries

1. **Interface first** — declare it in `Domain/`, implement it in `Application/`
   (actions, services) or `Infrastructure/` (repositories).
2. **Repositories are the only Eloquent gateway.** A controller, action or
   service that queries a model directly is a defect, not a shortcut.
3. **Cross-module calls go through the other module's `<Module>FacadeInterface`**,
   or through a narrow port that module implements. Never import another
   module's concrete class. There are currently no dependency cycles between
   modules — the paywall is the worked example: `Shared` declares
   `PaywallInvoiceIssuerInterface` and `Payment` implements it, so the arrow
   points one way.
4. **Every binding lives in the module's ServiceProvider** (`$singletons`). An
   interface without a binding fails at runtime, not during analysis.
5. **One use case = one Action** with a single `execute()` method.
6. **Validate at the boundary.** External payloads — Blockstream, the AI
   providers, Alby webhooks, the cache, the session — are untrusted and
   untyped. Parse them into known shapes where they enter (see
   `BlockstreamPayload`) rather than indexing into `mixed` deep inside.

Modules are registered by hand in `bootstrap/providers.php`; there is no
auto-discovery. Routes live in `routes/` and point at controllers under each
module's `Infrastructure/Http/Controller/`.

## Red flags

- `use Illuminate\` or `use App\Models\` inside `Domain/`
- Eloquent calls in a controller, action or service
- Another module's concrete class imported instead of its facade interface
- A new interface with no `$singletons` entry
- Module-specific code accumulating in `Shared/`
