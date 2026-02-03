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

The codebase uses a modular architecture with all business logic in `/modules/`. Each module follows a 4-layer structure:

```
Module/
├── Application/        # Use cases, services, actions, facades
├── Domain/            # Interfaces and domain data objects
├── Infrastructure/    # Controllers, repositories, middleware, HTTP requests
└── ModuleServiceProvider.php
```

**Core Modules:**
- **Blockchain** – Fetches data from Blockstream API
- **Chat** – Chat creation, messaging, history (ChatService, ChatFacade)
- **OpenAI** – GPT-4o API interactions
- **Nostr** – Decentralized authentication
- **Payment** – Lightning invoices via Alby
- **UtxoTrace** – UTXO tracing for transactions
- **Faq** – FAQ management
- **Shared** – Cross-cutting helpers, rate limiting middleware

**Key Patterns:**
- Action classes for use cases (CreateChatAction, AddMessageAction)
- Repository pattern for data access
- Facades for module APIs (ChatFacade, UtxoTraceFacade)
- Service providers bind interfaces to implementations

## Tech Stack

- Backend: Laravel 12.x, PHP 8.2+
- Frontend: Blade, Alpine.js 3.14, Tailwind CSS 4, Vite
- Database: SQLite
- External APIs: OpenAI, Blockstream, CoinGecko, Alby

## Code Style

- PHP CS Fixer + Rector for formatting
- PHPStan for static analysis
- Strict types enabled
- PSR-12 compliant

## Commit Guidelines

Always use conventional commits (feat, fix, refactor, chore, docs, test).
