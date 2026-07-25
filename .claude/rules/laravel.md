---
description: Laravel wiring — models, providers, routes, controllers, config, queues
globs:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "config/**/*.php"
  - "database/**/*.php"
  - "bootstrap/**/*.php"
  - "modules/*/Infrastructure/Http/**/*.php"
---

# Laravel Wiring

`app/` is glue only. No business logic — that belongs in `modules/`.

## Models

- Eloquent models live in `app/Models/`
- Only repositories (`modules/*/Infrastructure/Repository/`) may query them
- Models carry relations, casts, and scopes — never use-case logic

## Service providers

- One `<Module>ServiceProvider` per module, at the module root
- Bindings go in `public $singletons = [Interface::class => Impl::class]`
- Register new providers manually in `bootstrap/providers.php` — no auto-discovery
- Contextual or conditional bindings go in `register()`

## Routes

- Defined under `routes/`, wired by `modules/Shared/RouteServiceProvider.php`
- Point at `Modules\<Module>\Infrastructure\Http\Controller\<X>Controller`
- Validation belongs in a FormRequest under `Infrastructure/Http/Request/`, never inline in the controller

## Controllers

A controller does exactly three things:

1. Accept a validated FormRequest
2. Call an Action or Facade
3. Return a response or view

Anything else — a query, a conditional over domain state, an HTTP call — moves into `Application/`.

## Views & frontend

- Blade under `resources/views/`, Alpine.js + Tailwind v4, bundled by Vite
- `composer dev` runs server + queue + logs + vite together
- `npm run build` for production assets

## Config & secrets

- Read config through `config()` in Infrastructure only; inject the resolved values into Application classes
- Never call `env()` outside `config/` — it returns null once config is cached
- New env vars get an entry in `.env.example` and `.env.testing`

## Queues & streaming

- Chat responses stream via `CreateChatStreamAction` / `AddMessageStreamAction`
- Queue driver is `sync` in tests (`phpunit.xml`), so jobs run inline there
