# Create New Module

Create a new module following hexagonal architecture principles.

## Arguments
- $ARGUMENTS: Module name in PascalCase (e.g., "Analytics", "Notification")

## Instructions

Create a new module with the following structure:

```
modules/{ModuleName}/
├── Application/
│   └── {ModuleName}Facade.php
├── Domain/
│   ├── {ModuleName}FacadeInterface.php
│   └── Repository/
└── Infrastructure/
│   ├── Http/
│   │   └── Controller/
│   └── Repository/
└── {ModuleName}ServiceProvider.php
```

### Steps:

1. **Create the Domain layer first** (pure PHP, no dependencies):
   - Create `{ModuleName}FacadeInterface.php` with the module's public API

2. **Create the Application layer**:
   - Create `{ModuleName}Facade.php` implementing the interface

3. **Create the Infrastructure layer**:
   - Create empty Controller and Repository directories

4. **Create the ServiceProvider**:
   - Bind the FacadeInterface to Facade implementation
   - Use `$singletons` array for bindings

5. **Register the provider** in `config/app.php` under `providers`

### Template Files:

**Domain/{ModuleName}FacadeInterface.php:**
```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName}\Domain;

interface {ModuleName}FacadeInterface
{
    // Define module's public API methods
}
```

**Application/{ModuleName}Facade.php:**
```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName}\Application;

use Modules\{ModuleName}\Domain\{ModuleName}FacadeInterface;

final readonly class {ModuleName}Facade implements {ModuleName}FacadeInterface
{
    public function __construct(
        // Inject dependencies via interfaces
    ) {
    }
}
```

**{ModuleName}ServiceProvider.php:**
```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName};

use Illuminate\Support\ServiceProvider;
use Modules\{ModuleName}\Application\{ModuleName}Facade;
use Modules\{ModuleName}\Domain\{ModuleName}FacadeInterface;
use Override;

final class {ModuleName}ServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        {ModuleName}FacadeInterface::class => {ModuleName}Facade::class,
    ];

    #[Override]
    public function register(): void
    {
        // Contextual bindings if needed
    }
}
```

After creating, run `composer test` to verify the module loads correctly.
