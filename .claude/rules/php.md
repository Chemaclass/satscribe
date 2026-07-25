---
description: PHP style, class conventions, type and DTO naming, static analysis
globs:
  - "modules/**/*.php"
  - "app/**/*.php"
  - "tests/**/*.php"
  - "database/**/*.php"
  - "config/**/*.php"
  - "routes/**/*.php"
---

# PHP Conventions

## Style

- PSR-12 via php-cs-fixer + rector — auto-applied by the PostToolUse hook, no manual run needed
- `declare(strict_types=1);` in **every** file, no exceptions
- `final` classes by default; drop `final` only when inheritance is actually used
- `readonly` on immutable classes and properties
- Constructor property promotion, one promoted property per line, trailing comma
- PHP 8.2 target (`composer.json` pins the platform) — no 8.3+ syntax

```php
<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\CreateChatActionInterface;

final readonly class CreateChatAction implements CreateChatActionInterface
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
    ) {
    }

    public function execute(CreateChatTransfer $transfer): CreateChatActionResult
    {
        // ...
    }
}
```

## Naming

| Kind | Rule | Example |
|---|---|---|
| Type declarations (`type`) | **Always** `T` prefix | `TChatPayload` |
| `interface` | Not used in this project for data shapes — use types | — |
| Input DTO | `Transfer` suffix, **no** `T` prefix | `CreateChatTransfer` |
| Handler output DTO | `Result` suffix, **no** `T` prefix | `CreateChatActionResult` |
| Action | verb + noun + `Action` | `CreateChatAction`, `AddMessageAction` |
| Repository | entity + `Repository` | `MessageRepository` |
| Boolean method | `is/has/can/should` prefix | `isFlagged()` |

## Static analysis

```bash
composer phpstan   # larastan, config in phpstan.neon (level 1, paths: app, modules, tests)
```

Keep new code clean at the highest level you can — do not add `@phpstan-ignore` to silence a real type problem. Fix the type instead.

Common fixes:

- Missing return type → add it, `void` included
- `array` without shape → `@param list<int>` / `@return array<string, mixed>`
- Nullable access → guard, or narrow with an early return

## Method size

- Functions do one thing, ideally under 20 lines
- 3 arguments max — beyond that, introduce a `Transfer` object
- Command-query separation: a getter never mutates
- Specific exceptions from `Domain/`, never bare `\Exception`
- No leftover `dd()`, `dump()`, `var_dump()`, `ray()`
