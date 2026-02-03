# Create New Repository

Create a new Repository following the repository pattern with interface in Domain and implementation in Infrastructure.

## Arguments
- $ARGUMENTS: ModuleName/EntityName (e.g., "Chat/Message", "Payment/Transaction")

## Instructions

Repositories abstract data access. Interface lives in Domain (pure), implementation in Infrastructure.

### Files to create:

1. `modules/{Module}/Domain/Repository/{Entity}RepositoryInterface.php`
2. `modules/{Module}/Infrastructure/Repository/{Entity}Repository.php`
3. `tests/Unit/{Module}/{Entity}RepositoryTest.php`
4. Update `{Module}ServiceProvider.php`

### Templates:

**Domain/Repository/{Entity}RepositoryInterface.php:**
```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Domain\Repository;

use App\Models\{Entity};

interface {Entity}RepositoryInterface
{
    public function find(int $id): ?{Entity};

    public function save({Entity} $entity): {Entity};

    public function delete({Entity} $entity): void;

    // Add domain-specific query methods
    // public function findByStatus(string $status): array;
}
```

**Infrastructure/Repository/{Entity}Repository.php:**
```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Infrastructure\Repository;

use App\Models\{Entity};
use Modules\{Module}\Domain\Repository\{Entity}RepositoryInterface;

final readonly class {Entity}Repository implements {Entity}RepositoryInterface
{
    public function __construct(
        // Inject any dependencies (e.g., tracking ID, pagination config)
    ) {
    }

    public function find(int $id): ?{Entity}
    {
        return {Entity}::find($id);
    }

    public function save({Entity} $entity): {Entity}
    {
        $entity->save();
        return $entity;
    }

    public function delete({Entity} $entity): void
    {
        $entity->delete();
    }
}
```

**tests/Unit/{Module}/{Entity}RepositoryTest.php:**
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\{Module};

use App\Models\{Entity};
use Modules\{Module}\Infrastructure\Repository\{Entity}Repository;
use PHPUnit\Framework\TestCase;

final class {Entity}RepositoryTest extends TestCase
{
    public function test_find_returns_entity_when_exists(): void
    {
        // For unit tests, mock the model or use test doubles
        // For integration tests with database, use Feature tests with RefreshDatabase
    }

    public function test_find_returns_null_when_not_exists(): void
    {
        // Test the null case
    }
}
```

**Update ServiceProvider:**
```php
public $singletons = [
    // ... existing
    {Entity}RepositoryInterface::class => {Entity}Repository::class,
];
```

### Guidelines:

- Repository methods should be domain-focused (e.g., `findActiveByUser()` not `findByStatusAndDate()`)
- Keep queries in the repository, not in services or actions
- Return domain objects, not query builders
- Use constructor injection for configuration values
