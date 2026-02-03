# Run Tests

Execute tests with various options.

## Arguments
- $ARGUMENTS: Optional - specific test file, method filter, or "coverage"

## Instructions

### Run All Tests
```bash
composer test  # phpstan + phpunit
```

### Run PHPUnit Only
```bash
composer phpunit
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/Unit/{Module}/{TestFile}.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit --filter test_method_name
```

### Run Tests in a Module
```bash
vendor/bin/phpunit tests/Unit/Chat/
```

### Run with Verbose Output
```bash
vendor/bin/phpunit -v
```

### Run with Stop on Failure
```bash
vendor/bin/phpunit --stop-on-failure
```

### Test Organization

```
tests/
├── Unit/           # Fast, isolated (mock all dependencies)
│   ├── Blockchain/
│   ├── Chat/
│   ├── OpenAI/
│   ├── Payment/
│   ├── Shared/
│   └── UtxoTrace/
├── Feature/        # Integration (real database)
└── TestCase.php
```

### Test Naming Convention

```php
public function test_{what}_{scenario}_{expected}(): void
```

Examples:
- `test_execute_with_valid_input_returns_chat()`
- `test_execute_with_cached_result_skips_api_call()`
- `test_sanitize_removes_flagged_words()`

### After Test Failures

1. Read the failure message
2. Check the assertion that failed
3. Verify test setup (Arrange phase)
4. Check the actual vs expected values
5. Debug with `dump()` or `var_dump()` if needed
6. Fix the code or the test (whichever is wrong)

### Quick Debugging

```php
// In test
dump($result);  // Pretty print
var_dump($result);  // Raw dump

// PHPUnit specific
$this->fail('Debug message: ' . print_r($data, true));
```
