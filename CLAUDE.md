# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Satscribe transforms Bitcoin blocks and transactions into human-readable AI conversations. Users input a transaction ID, block hash, or block height; the app fetches blockchain data via Blockstream API and generates explanations using OpenAI GPT-4o. Authentication uses Nostr protocol (no traditional accounts).

## Development Commands

```bash
composer dev          # Run full dev environment (server + queue + logs + vite)
composer test         # Run phpstan + phpunit
composer fix          # Run rector + php-cs-fixer
composer phpunit      # Run unit tests only
composer phpstan      # Run static analysis only
```

**Run a single test file:**
```bash
vendor/bin/phpunit tests/Unit/Chat/ChatRepositoryTest.php
```

**Run a single test method:**
```bash
vendor/bin/phpunit --filter testMethodName
```

**Enable git hooks:**
```bash
git config core.hooksPath githooks
```

## Architecture

The codebase uses a **modular hexagonal architecture** with all business logic in `/modules/`. Each module follows a 4-layer structure:

```
Module/
├── Application/        # Use cases, services, actions, facades
├── Domain/            # Interfaces and domain data objects (NEVER depends on Infrastructure)
├── Infrastructure/    # Controllers, repositories, middleware, HTTP requests
└── ModuleServiceProvider.php
```

### Architecture Rules (MUST follow)

1. **Domain layer is pure** - No Laravel, no infrastructure dependencies. Only PHP interfaces and value objects.
2. **Dependency direction** - Infrastructure → Application → Domain. Never the reverse.
3. **Interface in Domain, implementation in Infrastructure** - All repository interfaces live in `Domain/Repository/`, implementations in `Infrastructure/Repository/`.
4. **Actions for use cases** - Each use case is an Action class in Application with a single `execute()` method.
5. **Facades expose module APIs** - Other modules communicate via `*FacadeInterface` from Domain layer.
6. **Service Provider binds contracts** - All interface→implementation bindings in `ModuleServiceProvider`.

### Layer Responsibilities

| Layer | Contains | Depends On | Never Contains |
|-------|----------|------------|----------------|
| **Domain** | Interfaces, Enums, Value Objects, Exceptions | Nothing | Laravel classes, external libs |
| **Application** | Actions, Services, Facades | Domain | Controllers, HTTP |
| **Infrastructure** | Controllers, Repositories, Middleware, Commands | Application, Domain | Business logic |

### Creating New Code

**New Action (use case):**
```
modules/{Module}/Domain/{Name}ActionInterface.php    # Interface first
modules/{Module}/Application/{Name}Action.php        # Implementation
tests/Unit/{Module}/{Name}ActionTest.php             # Test
```

**New Repository:**
```
modules/{Module}/Domain/Repository/{Name}RepositoryInterface.php
modules/{Module}/Infrastructure/Repository/{Name}Repository.php
tests/Unit/{Module}/{Name}RepositoryTest.php
```

**New Module:**
```
modules/{Module}/
├── Application/
├── Domain/
├── Infrastructure/
│   ├── Http/Controller/
│   └── Repository/
└── {Module}ServiceProvider.php
```

### Core Modules

- **Blockchain** – Fetches data from Blockstream API
- **Chat** – Chat creation, messaging, history (ChatService, ChatFacade)
- **OpenAI** – GPT-4o API interactions
- **Nostr** – Decentralized authentication
- **Payment** – Lightning invoices via Alby
- **UtxoTrace** – UTXO tracing for transactions
- **Faq** – FAQ management
- **Shared** – Cross-cutting helpers, rate limiting middleware

## TDD Workflow

Follow Red-Green-Refactor:

1. **Red** - Write a failing test first that describes the behavior
2. **Green** - Write minimal code to make it pass
3. **Refactor** - Clean up while keeping tests green

### Test Organization

```
tests/
├── Unit/           # Fast, isolated tests (mock dependencies)
│   └── {Module}/   # Mirror module structure
├── Feature/        # Integration tests (real database)
└── TestCase.php    # Base test class
```

### Test Naming Convention

```php
public function test_{action}_{condition}_{expected_result}(): void
// Example:
public function test_execute_with_cached_chat_returns_existing(): void
public function test_sanitize_removes_flagged_words(): void
```

## Code Style

- PHP CS Fixer + Rector for formatting
- PHPStan for static analysis (level max)
- Strict types enabled in ALL files
- PSR-12 compliant
- `final` classes by default (extend only when necessary)
- `readonly` for immutable properties
- Constructor property promotion

### Class Conventions

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\{Layer};

final readonly class ExampleAction implements ExampleActionInterface
{
    public function __construct(
        private SomeDependencyInterface $dependency,
    ) {
    }

    public function execute(InputDto $input): OutputDto
    {
        // ...
    }
}
```

## Commit Guidelines

Always use conventional commits: `feat`, `fix`, `refactor`, `chore`, `docs`, `test`.

Examples:
- `feat: add UTXO tracing for transaction inputs`
- `fix: handle null response from OpenAI API`
- `refactor: extract rate limiting to dedicated middleware`
- `test: add unit tests for ChatRepository pagination`

## Code Review Checklist

Before completing any change, verify:

- [ ] Tests pass: `composer test`
- [ ] Code style: `composer fix`
- [ ] No architecture violations (Domain must not import Infrastructure)
- [ ] Interfaces defined before implementations
- [ ] Service provider updated with new bindings
- [ ] No business logic in controllers (delegate to Actions)
