# Satscribe

Laravel 12 · Modular Hexagonal · TDD · Bitcoin blocks/txs → AI chats via GPT-4o · Nostr auth (no passwords)
Modules: Blockchain, Chat, Faq, Nostr, OpenAI, Payment, Shared, UtxoTrace

## Hard Rules

- **Layers**: Infrastructure → Application → Domain. Domain never imports `Modules\*\Infrastructure\*`, Laravel runtime, or facades
- **DB**: repositories in `Infrastructure/Repository/` are the only Eloquent gateway. Controllers, actions and services never query
- **Interfaces first**: declare in `Domain/`, implement in `Application/` (actions) or `Infrastructure/` (repositories), bind in `<Module>ServiceProvider::$singletons`
- **Cross-module**: only via the other module's `<Module>FacadeInterface`. Never import a concrete class from another module
- **Every file**: `declare(strict_types=1);` · `final` unless inherited · `readonly` where immutable · constructor promotion
- **Naming**: type declarations `T`-prefixed · input DTOs `Transfer` suffix · handler outputs `Result` suffix — no `T` on DTOs
- **Tests**: failing test first. `mock()` directly, never `Mockery::mock()`. Mock `Domain/` interfaces, never concrete classes
- **Commits**: `<type>(<scope>): <description>` — imperative, ≤80 chars, no trailing period. `ref:` not `refactor:`. Never mention AI/Claude
- **Providers**: registered by hand in `bootstrap/providers.php` — no auto-discovery

## Never Do

- Business logic in `app/` — it holds Laravel glue only (Models, framework providers)
- An interface without its `$singletons` binding — fails at runtime, not in phpstan
- `env()` outside `config/` — returns null once config is cached
- Production code before its failing test · commit on red · `--no-verify`
- Leave `dd()`, `dump()`, `var_dump()`, `ray()` behind
- Unit tests that touch the DB or network

## Commands

| Command | What |
|---|---|
| `composer test` | phpstan + phpunit — the gate |
| `composer fix` | rector + php-cs-fixer |
| `composer dev` | server + queue + logs + vite |
| `composer phpunit` / `composer phpstan` | tests only / analysis only |
| `vendor/bin/phpunit --testsuite=unit` | fast loop |
| `vendor/bin/phpunit --filter <name>` | one class or method |
| `git config core.hooksPath githooks` | enable pre-commit gate (once per clone) |

## Reference — read the rule file when working in that area

| File | Covers |
|---|---|
| `.claude/rules/architecture.md` | Layers, module layout, Domain purity trade, facades, bindings |
| `.claude/rules/php.md` | Style, class conventions, type/DTO naming, phpstan |
| `.claude/rules/testing.md` | Test layout, suites, mocking, naming |
| `.claude/rules/laravel.md` | Models, providers, routes, controllers, config, queues |
| `.claude/rules/error-handling.md` | External APIs, domain exceptions, streaming failures |
| `.claude/rules/frontend.md` | Blade, Alpine, Tailwind v4, Vite, Nostr browser auth |
| `.claude/rules/commits.md` | Commit format, branch hygiene, PR creation |

## Agents & Skills

| Need | Use |
|---|---|
| Fast file/code search | `explorer` (haiku) |
| Code review | `clean-code-reviewer` · `/review` |
| TDD coaching | `tdd-coach` · `/tdd` |
| Layer boundaries, placement | `hexagonal-architect` · `/architecture-check` |
| Root-cause a failure | `debugger` |
| Scaffold | `/create-action` `/create-repository` `/create-controller` `/create-module` |
| Everyday | `/test` `/fix` `/commit` `/pr` `/refactor` `/module-map` |

## Enforcement

| Hook | What |
|---|---|
| `pre-bash` | Blocks DB drops, mass `rm`, `--no-verify`, plain force-push |
| `pre-write` | Blocks logic in `app/` and Infra imports in `Domain/`. Blocks **new** / warns existing for Laravel leakage and Eloquent outside repositories |
| `post-edit` | Auto-formats PHP (php-cs-fixer) |
| `post-commit-msg` | Blocks AI attribution, warns on non-conventional format |
| `pre-stop` | Warns on uncommitted changes |
| `githooks/pre-commit` | `composer fix && composer test` |

## Known Debt

Pre-existing violations — do not replicate; fix opportunistically when already in the file:

| Location | Issue |
|---|---|
| `modules/Chat/Application/HistoryService.php:48` | `Message::find()` in Application — belongs in `MessageRepository` |
| `modules/Nostr/.../ProfileController.php:21,25` | `Chat::where()` / `Payment::where()` in a controller |
| 9 `Domain/` files (Chat, OpenAI, Payment, UtxoTrace) | Import `App\Models\*` — accepted legacy trade, see `rules/architecture.md` |
| `Faq`/`Nostr`/`Payment` `FacadeInterface` | Declared and implemented, but never bound in `$singletons` and never injected. Dormant — injecting one today throws "not instantiable". Bind it when you first use it |

## Gotchas

- `phpstan.neon` is **level 5** across `app`, `modules` and `tests`. Mockery's expectation API is invisible to PHPStan, so `mock()->expects()` is suppressed by an `ignoreErrors` rule scoped to `tests/*` — don't widen it to production code
- Streaming replies (`Chat/Application/*StreamAction.php`) fail as truncated output, not exceptions — headers are already sent
- External APIs (Blockstream, OpenAI, Alby) must be wrapped in try/catch — network failure is expected, not exceptional
- Queue driver is `sync` in tests, so jobs run inline
- After a rename, grep string literals too — route paths, i18n keys, seeders. A stale `"/old-path"` is a production outage
