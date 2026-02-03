# Fix Code Style

Run all code style fixers and static analysis.

## Instructions

Execute the following in order:

### 1. Run Rector (automated refactoring)
```bash
composer rector
```

### 2. Run PHP CS Fixer (code style)
```bash
composer php-cs-fixer
```

### 3. Run PHPStan (static analysis)
```bash
composer phpstan
```

Or simply:
```bash
composer fix && composer phpstan
```

### Common Issues and Fixes

**Missing strict_types:**
```php
<?php

declare(strict_types=1);
```

**Missing return type:**
```php
public function getName(): string
```

**Missing property type:**
```php
private string $name;
// or
private readonly string $name;
```

**Array type hints:**
```php
/** @var array<string, mixed> */
private array $data;

/** @param list<int> $ids */
public function process(array $ids): void
```

### If PHPStan Fails

1. Read the error message carefully
2. Check the line number mentioned
3. Common fixes:
   - Add type hints
   - Handle null cases
   - Fix incorrect types
   - Add `@var` annotations for complex types

### After Fixing

Run the full test suite to ensure nothing broke:
```bash
composer test
```
