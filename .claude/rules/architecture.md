---
description: Module structure, hexagonal layers, layer boundaries, where new code goes
globs:
  - "modules/**/*.php"
  - "app/**/*.php"
  - "bootstrap/providers.php"
  - "routes/**/*.php"
---

# Modular Hexagonal Architecture

All business logic lives in `modules/<Module>/`. `app/` holds only Laravel glue (Models, Providers, framework wiring).

```
modules/<Module>/
├── Application/                 # Actions, Services, Facades
├── Domain/                      # Interfaces, Enums, Value Objects, DTOs, Exceptions
│   ├── Repository/              # Repository interfaces
│   ├── Data/                    # Value objects, DTOs
│   ├── Enum/
│   └── ViewModel/
├── Infrastructure/              # Controllers, Repositories, Middleware, Commands, Requests
│   ├── Http/Controller/
│   ├── Http/Request/
│   ├── Repository/
│   └── Command/
└── <Module>ServiceProvider.php
```

## Layer contract

| Layer | Contains | May depend on | Must never contain |
|---|---|---|---|
| **Domain** | Interfaces, Enums, Value Objects, DTOs, Exceptions | Nothing (see purity note) | Infrastructure imports, Laravel runtime, facades |
| **Application** | Actions, Services, Facades | Domain | Controllers, HTTP, Requests, DB queries |
| **Infrastructure** | Controllers, Repositories, Middleware, Commands | Application, Domain | Business logic |

Dependency direction is one-way: **Infrastructure → Application → Domain**.

## Domain purity — what this project actually does

Strict-hexagonal Domain forbids every framework type. This codebase makes a deliberate, narrower trade: **repository interfaces return Eloquent models**, and Domain references a small set of Laravel value types.

Accepted in `Domain/` today:

- `App\Models\*` — as return/param types on repository interfaces and DTOs (`ChatRepositoryInterface`, `CreateChatActionResult`, `HistoryChatItem`)
- `Illuminate\Support\Collection`, `Illuminate\Contracts\Pagination\Paginator` — collection shapes
- `Illuminate\Http\Client\{PendingRequest,Response}` — on `Shared\Domain\HttpClientInterface`

Not accepted in `Domain/`:

- **Any `Modules\*\Infrastructure\*` import** — zero today, keep it that way. Hard-blocked by the `pre-write` hook
- Laravel runtime components: `Illuminate\{Database,Foundation,Routing,Console,Queue,Mail,Cache,Auth}\*`
- Facades (`Illuminate\Support\Facades\*`) — inject the contract

The `pre-write` hook **blocks** these in new files and **warns** in existing ones, so known debt stays editable without growing.

If you are starting a genuinely new module, prefer keeping models out of its Domain — the trade above is a legacy convenience in `Chat`, `OpenAI`, `Payment`, and `UtxoTrace`, not a target to replicate.

## Hard rules

1. **Interface first** — declare the interface in `Domain/`, then implement it. Actions land in `Application/`, repositories in `Infrastructure/Repository/`.
2. **Repositories are the only DB gateway** — controllers, actions and services never touch Eloquent. A controller calling `Chat::where(...)` is a defect, not a shortcut.
3. **Facades are the module's public API** — cross-module calls go through `<Module>FacadeInterface` from the other module's `Domain/`. Never import another module's Application or Infrastructure class.
4. **Every binding lives in the ModuleServiceProvider** — `public $singletons = [Interface::class => Impl::class]`. A new interface without a binding fails at runtime, not at analysis time.
5. **Constructor injection by interface only** — including Laravel facades: inject the contract, not the static facade.
6. **One use case = one Action** with a single `execute()` method.

## Registering a module

New modules are registered by hand in `bootstrap/providers.php` — there is no auto-discovery. Routes live in `routes/` and point at controllers under `Infrastructure/Http/Controller/`.

## Current modules

| Module | Responsibility |
|---|---|
| `Blockchain` | Blockstream API — blocks, transactions, addresses |
| `Chat` | Chat creation, messaging, history, flagged words |
| `OpenAI` | GPT-4o calls, streaming responses |
| `Nostr` | Decentralized auth (no passwords) |
| `Payment` | Lightning invoices via Alby |
| `UtxoTrace` | UTXO tracing across transaction inputs |
| `Faq` | FAQ pages |
| `Shared` | Cross-cutting helpers, rate limiting, middleware |

## Red flags

- `use Illuminate\` or `use App\Models\` inside `Domain/`
- Eloquent calls in a controller, action, or service
- `new SomeAction(...)` instead of container injection
- Another module's concrete class imported instead of its Facade interface
- Business logic in `Infrastructure/Http/Controller/`
- Module-specific code piling up in `Shared/`
- A new interface with no `$singletons` entry
