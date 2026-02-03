# Refactor Code

Refactor code following clean code principles while maintaining test coverage.

## Arguments
- $ARGUMENTS: File path or description of what to refactor

## Instructions

### Pre-Refactor Checklist

1. **Ensure tests exist** for the code being refactored
2. **Run tests** to establish a green baseline
3. **Understand the current behavior** before changing

```bash
composer test
```

### Refactoring Principles

#### Single Responsibility Principle (SRP)
- Each class should have one reason to change
- If a class does multiple things, extract into separate classes

#### Open/Closed Principle (OCP)
- Open for extension, closed for modification
- Use interfaces and composition over inheritance

#### Liskov Substitution Principle (LSP)
- Subtypes must be substitutable for their base types
- Don't violate interface contracts

#### Interface Segregation Principle (ISP)
- Many small interfaces > one large interface
- Clients shouldn't depend on methods they don't use

#### Dependency Inversion Principle (DIP)
- Depend on abstractions, not concretions
- High-level modules shouldn't depend on low-level modules

### Common Refactorings

#### Extract Method
```php
// Before
public function process(): void
{
    // 20 lines of validation
    // 20 lines of processing
    // 20 lines of saving
}

// After
public function process(): void
{
    $this->validate();
    $this->processData();
    $this->save();
}
```

#### Extract Class
```php
// Before: Action doing too much
final class CreateOrderAction
{
    public function execute(): Order
    {
        // validate
        // calculate totals
        // apply discounts
        // save
    }
}

// After: Separate concerns
final class CreateOrderAction
{
    public function __construct(
        private OrderValidator $validator,
        private PriceCalculator $calculator,
    ) {}
}
```

#### Replace Conditional with Polymorphism
```php
// Before
if ($type === 'email') {
    $this->sendEmail();
} elseif ($type === 'sms') {
    $this->sendSms();
}

// After
interface NotificationSender { public function send(): void; }
class EmailSender implements NotificationSender { ... }
class SmsSender implements NotificationSender { ... }
```

#### Introduce Parameter Object
```php
// Before
public function search(string $query, int $page, int $limit, string $sort): array

// After
public function search(SearchCriteria $criteria): array
```

### Refactoring Workflow

1. **Identify the smell** - What's wrong with the current code?
2. **Choose the refactoring** - Which technique applies?
3. **Make small changes** - One refactoring at a time
4. **Run tests after each change** - Ensure nothing breaks
5. **Commit frequently** - Small, atomic commits

### After Each Change

```bash
vendor/bin/phpunit  # Quick test run
```

### After Refactoring Complete

```bash
composer test   # Full test suite + static analysis
composer fix    # Code style
```

### Code Smells to Watch For

- **Long method** → Extract methods
- **Long class** → Extract class
- **Long parameter list** → Introduce parameter object
- **Duplicate code** → Extract method/class
- **Feature envy** → Move method to the class it uses most
- **Data clumps** → Create value object
- **Primitive obsession** → Create domain types
- **Switch statements** → Replace with polymorphism
- **Parallel inheritance** → Consolidate hierarchies
- **Comments explaining "what"** → Rename to be self-documenting
