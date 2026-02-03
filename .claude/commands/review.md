# Code Review

Perform a comprehensive code review focused on architecture, clean code, and best practices.

## Arguments
- $ARGUMENTS: File path(s) or "staged" for git staged changes, or "branch" for current branch changes

## Instructions

### 1. Gather Changes

**For staged changes:**
```bash
git diff --cached --name-only
git diff --cached
```

**For branch changes:**
```bash
git diff main...HEAD --name-only
git diff main...HEAD
```

**For specific file:**
Read the file directly.

### 2. Architecture Review

Check each changed file against these rules:

| Layer | Allowed Dependencies | Forbidden |
|-------|---------------------|-----------|
| Domain | Nothing | Laravel, Infrastructure |
| Application | Domain | Controllers, HTTP |
| Infrastructure | Application, Domain | - |

**Questions to ask:**
- Does this respect dependency direction?
- Is the interface in Domain and implementation in Application/Infrastructure?
- Is the ServiceProvider updated with new bindings?

### 3. Clean Code Review

**Naming:**
- [ ] Classes named as nouns (e.g., `ChatRepository`, `CreateChatAction`)
- [ ] Methods named as verbs (e.g., `execute()`, `findByUser()`)
- [ ] Variables reveal intent (no `$data`, `$temp`, `$x`)
- [ ] Boolean methods start with `is/has/can/should`

**Functions/Methods:**
- [ ] Do one thing only
- [ ] Small (< 20 lines ideally)
- [ ] Few arguments (< 4, prefer parameter objects)
- [ ] No side effects for query methods
- [ ] Command-query separation

**Classes:**
- [ ] Single responsibility
- [ ] Small and focused
- [ ] High cohesion (related methods/properties)
- [ ] Low coupling (few dependencies)

### 4. SOLID Principles Check

- **S**ingle Responsibility: Does each class have one reason to change?
- **O**pen/Closed: Can behavior be extended without modifying existing code?
- **L**iskov Substitution: Can implementations be swapped without breaking code?
- **I**nterface Segregation: Are interfaces focused and minimal?
- **D**ependency Inversion: Does code depend on abstractions?

### 5. Testing Review

- [ ] Unit tests exist for new code
- [ ] Tests follow Arrange-Act-Assert pattern
- [ ] Test names describe behavior (`test_execute_with_invalid_input_throws_exception`)
- [ ] Edge cases covered
- [ ] Mocks used appropriately (not over-mocked)

### 6. Security Review

- [ ] No hardcoded secrets
- [ ] Input validated/sanitized
- [ ] SQL injection prevented (parameterized queries)
- [ ] XSS prevented (output escaped)
- [ ] No sensitive data in logs

### 7. Performance Considerations

- [ ] No N+1 queries (use eager loading)
- [ ] Appropriate caching
- [ ] No unnecessary loops
- [ ] Database indexes for query columns

### 8. Code Style

```bash
composer fix   # Auto-fix style issues
composer phpstan  # Static analysis
```

### Review Output Format

For each issue found, provide:

```
**[SEVERITY]** File:Line - Category
Description of the issue.
Suggested fix or example.
```

Severity levels:
- **CRITICAL**: Must fix before merge (security, data loss, broken functionality)
- **MAJOR**: Should fix (architecture violation, significant code smell)
- **MINOR**: Nice to fix (style, minor improvements)
- **SUGGESTION**: Optional improvement

### Final Summary

Provide:
1. Overall assessment (Approve / Request Changes / Needs Discussion)
2. Count of issues by severity
3. Key strengths of the changes
4. Priority items to address
