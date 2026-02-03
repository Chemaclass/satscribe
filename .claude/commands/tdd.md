# TDD Workflow

Start a Test-Driven Development workflow for implementing a feature.

## Arguments
- $ARGUMENTS: Description of the feature or behavior to implement

## Instructions

Follow the Red-Green-Refactor cycle strictly:

### Phase 1: RED - Write a Failing Test

1. **Understand the requirement** from the arguments
2. **Identify which module** this belongs to
3. **Create the test file** in `tests/Unit/{Module}/` or `tests/Feature/`
4. **Write the test** that describes the expected behavior
5. **Run the test** - it MUST fail (proves the test is valid)

```bash
vendor/bin/phpunit tests/Unit/{Module}/{TestName}.php
```

### Phase 2: GREEN - Make It Pass

1. **Write minimal code** to make the test pass
2. **Don't over-engineer** - just enough to pass
3. **Run the test** - it should pass now

```bash
vendor/bin/phpunit tests/Unit/{Module}/{TestName}.php
```

### Phase 3: REFACTOR - Clean Up

1. **Improve the code** while keeping tests green
2. **Apply clean code principles:**
   - Extract methods if too long
   - Improve naming
   - Remove duplication
3. **Run full test suite** to ensure no regressions

```bash
composer test
```

### Test Structure (Arrange-Act-Assert)

```php
public function test_action_scenario_expected(): void
{
    // Arrange: Set up test data and mocks
    $mock = $this->createMock(DependencyInterface::class);
    $mock->method('someMethod')->willReturn($value);

    $sut = new SystemUnderTest($mock); // SUT = System Under Test

    // Act: Execute the behavior being tested
    $result = $sut->execute($input);

    // Assert: Verify the outcome
    $this->assertSame($expected, $result);
}
```

### Test Types

| Type | Location | Purpose | Database |
|------|----------|---------|----------|
| Unit | `tests/Unit/` | Test single class in isolation | No |
| Feature | `tests/Feature/` | Test integration with framework | Yes (RefreshDatabase) |

### Unit Test Guidelines

- Mock ALL external dependencies
- Test one behavior per test method
- Use descriptive test names
- Test edge cases and error conditions
- Keep tests fast (< 100ms each)

### Feature Test Guidelines

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

final class SomeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_returns_expected(): void
    {
        // Setup database state
        // Make HTTP request
        // Assert response
    }
}
```

### When to Write Which

- **Unit test**: Business logic, actions, services, value objects
- **Feature test**: HTTP endpoints, database queries, external integrations

### Common Assertions

```php
$this->assertSame($expected, $actual);      // Strict equality
$this->assertEquals($expected, $actual);    // Loose equality
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertNull($value);
$this->assertInstanceOf(ClassName::class, $object);
$this->assertCount(3, $array);
$this->assertArrayHasKey('key', $array);
$this->expectException(SomeException::class);
```

### After Implementation

Run the full quality check:
```bash
composer test  # phpstan + phpunit
composer fix   # rector + php-cs-fixer
```
