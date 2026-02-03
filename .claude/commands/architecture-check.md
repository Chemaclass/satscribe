# Architecture Check

Validate the codebase follows hexagonal architecture principles.

## Instructions

Perform the following architecture validations:

### 1. Dependency Direction Check

Verify that dependencies flow correctly: Infrastructure → Application → Domain

**Violations to find:**
- Domain layer importing from Infrastructure (`use Modules\*\Infrastructure\*` in Domain files)
- Domain layer importing Laravel classes (`use Illuminate\*` in Domain files)
- Application layer importing Controllers or Middleware

```bash
# Check for Domain layer violations
grep -r "use Illuminate" modules/*/Domain/ 2>/dev/null || echo "OK: No Laravel imports in Domain"
grep -r "use Modules\\\\.*\\\\Infrastructure" modules/*/Domain/ 2>/dev/null || echo "OK: No Infrastructure imports in Domain"
```

### 2. Interface-Implementation Pairing

Verify every repository and action has:
- Interface in Domain layer
- Implementation in Application/Infrastructure layer
- Binding in ServiceProvider

```bash
# List all interfaces
find modules -name "*Interface.php" -path "*/Domain/*"

# List all implementations (should match interfaces)
find modules -name "*.php" -path "*/Application/*" -o -name "*.php" -path "*/Infrastructure/Repository/*"
```

### 3. ServiceProvider Bindings

Check that all interfaces are bound in their module's ServiceProvider:
- Open each `*ServiceProvider.php`
- Verify `$singletons` array contains all interface→implementation pairs
- Verify no concrete classes are type-hinted in constructors

### 4. Controller Responsibility

Controllers should ONLY:
- Validate input (via Request classes)
- Delegate to Actions/Services
- Return responses

**Red flags in controllers:**
- Database queries (`Model::where()`)
- Business logic conditionals
- Direct API calls

### 5. Test Coverage Check

```bash
# Run tests with coverage (if configured)
vendor/bin/phpunit --coverage-text

# At minimum, run full test suite
composer test
```

### 6. Code Style Compliance

```bash
composer fix  # rector + php-cs-fixer
composer phpstan  # static analysis
```

### Architecture Checklist

- [ ] No Laravel imports in Domain layer
- [ ] No Infrastructure imports in Domain layer
- [ ] All Action interfaces in Domain, implementations in Application
- [ ] All Repository interfaces in Domain, implementations in Infrastructure
- [ ] All bindings present in ServiceProvider
- [ ] Controllers delegate to Actions (no business logic)
- [ ] All tests pass
- [ ] PHPStan passes
- [ ] Code style is clean

### Common Violations to Fix

1. **Model in Domain** → Create a Domain interface, keep Model in Infrastructure
2. **Service doing HTTP** → Extract to Infrastructure, inject via interface
3. **Business logic in Controller** → Move to Action class
4. **Concrete dependency** → Create interface, bind in ServiceProvider

Report any violations found and suggest fixes.
