# Create New Action (Use Case)

Create a new Action class following TDD and hexagonal architecture.

## Arguments
- $ARGUMENTS: ModuleName/ActionName (e.g., "Chat/DeleteChat", "Payment/RefundInvoice")

## Instructions

Actions represent use cases in the Application layer. Follow TDD: write the test first.

### Files to create (in order):

1. `modules/{Module}/Domain/{ActionName}ActionInterface.php` - Interface
2. `tests/Unit/{Module}/{ActionName}ActionTest.php` - Test (write BEFORE implementation)
3. `modules/{Module}/Application/{ActionName}Action.php` - Implementation
4. Update `{Module}ServiceProvider.php` with binding

### TDD Workflow:

**Step 1: Define the interface (Domain layer)**
```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Domain;

interface {ActionName}ActionInterface
{
    public function execute(/* typed parameters */): /* return type */;
}
```

**Step 2: Write the test FIRST (Red phase)**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\{Module};

use Modules\{Module}\Application\{ActionName}Action;
use Modules\{Module}\Domain\{ActionName}ActionInterface;
use PHPUnit\Framework\TestCase;

final class {ActionName}ActionTest extends TestCase
{
    public function test_execute_does_expected_behavior(): void
    {
        // Arrange: Create mocks for dependencies
        $dependency = $this->createMock(SomeDependencyInterface::class);

        // Set expectations
        $dependency->expects($this->once())
            ->method('someMethod')
            ->willReturn($expectedValue);

        $action = new {ActionName}Action($dependency);

        // Act
        $result = $action->execute($input);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function test_execute_with_edge_case(): void
    {
        // Test edge cases and error conditions
    }
}
```

**Step 3: Run the test (should fail)**
```bash
vendor/bin/phpunit tests/Unit/{Module}/{ActionName}ActionTest.php
```

**Step 4: Implement the action (Green phase)**
```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Application;

use Modules\{Module}\Domain\{ActionName}ActionInterface;

final readonly class {ActionName}Action implements {ActionName}ActionInterface
{
    public function __construct(
        private SomeDependencyInterface $dependency,
    ) {
    }

    public function execute(/* parameters */): /* return type */
    {
        // Minimal implementation to pass the test
    }
}
```

**Step 5: Run the test again (should pass)**

**Step 6: Refactor if needed, keeping tests green**

**Step 7: Update ServiceProvider**
```php
public $singletons = [
    // ... existing bindings
    {ActionName}ActionInterface::class => {ActionName}Action::class,
];
```

**Step 8: Run full test suite**
```bash
composer test
```

### Naming Conventions:

- Action names: verb + noun (e.g., `CreateChat`, `DeletePayment`, `ValidateInvoice`)
- Test methods: `test_{action}_{scenario}_{expected}`
- One public method: `execute()`
