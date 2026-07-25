---
description: TDD workflow, test layout, naming, mocking, which suite to write
globs:
  - "tests/**/*.php"
  - "phpunit.xml"
---

# Testing

## Layout

```
tests/
├── Unit/<Module>/     # Fast, isolated. Mock every dependency. No DB, no HTTP.
├── Feature/           # Full framework. Real DB (RefreshDatabase), routes, middleware.
└── TestCase.php
```

`tests/Unit/` mirrors `modules/` one-to-one. A test for `modules/Chat/Application/CreateChatAction.php` goes in `tests/Unit/Chat/CreateChatActionTest.php`.

## Naming

```php
public function test_{action}_{condition}_{expected_result}(): void
```

```php
public function test_execute_with_cached_chat_returns_existing(): void
public function test_execute_with_flagged_word_throws_exception(): void
public function test_sanitize_removes_flagged_words(): void
```

## Mocking

Use `mock()` directly — never `Mockery::mock()`:

```php
$repository = mock(ChatRepositoryInterface::class);
$repository->shouldReceive('findByTxid')
    ->once()
    ->with('abc123')
    ->andReturn($chat);
```

Mock the **interface from `Domain/`**, never a concrete class. If a test forces you to mock a concrete type, the production code is missing an abstraction.

## Which suite

| Testing | Suite | Why |
|---|---|---|
| Action, Service, value object, sanitizer | Unit | Pure logic, mock the ports |
| Repository query behavior | Feature | Needs a real DB |
| HTTP endpoint, middleware, auth | Feature | Needs the framework |
| External API client (Blockstream, OpenAI, Alby) | Unit | Fake the HTTP client interface |

## Structure

Arrange-Act-Assert, one behavior per test:

```php
public function test_execute_with_valid_txid_returns_chat(): void
{
    // Arrange
    $repository = mock(ChatRepositoryInterface::class);
    $repository->shouldReceive('save')->once()->andReturn($chat);
    $action = new CreateChatAction($repository);

    // Act
    $result = $action->execute($transfer);

    // Assert
    self::assertSame($chat, $result->chat);
}
```

Prefer `assertSame` over `assertEquals`. Assert on behavior and outputs, not on internals.

## Commands

```bash
composer test                                  # phpstan + phpunit — the gate
composer phpunit                               # tests only
vendor/bin/phpunit --testsuite=unit            # fast loop
vendor/bin/phpunit tests/Unit/Chat/            # one module
vendor/bin/phpunit --filter test_method_name   # one test
```

## Red flags

- Production code written before its test
- A test that passes the first time it runs (it proved nothing)
- Several behaviors asserted in one test method
- Assertions on private state or implementation details
- Everything mocked — the test then only describes the mocks
- Unit tests that hit the DB or the network
