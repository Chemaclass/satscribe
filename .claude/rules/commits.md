---
description: Commit format, branch hygiene, PR creation, CI verification
globs:
  - ".github/**"
  - "githooks/**"
---

# Commits & PRs

## Conventional commits

```
<type>(<scope>): <subject>
```

| Type | Use for |
|---|---|
| `feat` | New user-facing capability |
| `fix` | Bug fix |
| `ref` | Restructuring, no behavior change (**`ref`, not `refactor`**) |
| `test` | Tests only |
| `docs` | Documentation only |
| `chore` | Tooling, deps, config |

Scope is the module in lowercase when the change is confined to one: `feat(chat): stream assistant replies`.

Rules:

- Subject in imperative mood, under ~70 chars, no trailing period
- Body only when the *why* isn't obvious from the subject
- **Never mention AI tooling.** No `Co-Authored-By`, no Claude/Anthropic trailer, no emoji in commit subjects
- Never `--amend` a pushed commit

## Git identity

`chemaclass@outlook.es`, GPG signing key `E51B5BF45F85D160`. Never the archived work address.

## Pre-commit hook

`githooks/pre-commit` runs `composer fix && composer test`. Enable once per clone:

```bash
git config core.hooksPath githooks
```

Run `composer fix && composer test` yourself before committing — a failing hook after a slow phpstan run wastes a minute.

## Pull requests

- Assign to Chemaclass: `gh pr create --assignee Chemaclass`
- Label to match the change: `bug`, `enhancement`, `documentation`, `refactoring`, `dependencies`
- Use `Closes #<n>` so merging closes the issue
- Body follows `.github/PULL_REQUEST_TEMPLATE.md` — read the file, use its exact headers (`## 📚 Description`, `## 🔖 Changes`)
- Keep the body short: what changed and why, not a diff narration

## Renames

After any cross-codebase rename, grep **all** references before merging — not just the symbol: URL literals and route paths, frontend stores, i18n keys, seeders, DB columns. A renamed class with a stale `"/old-path"` string is a production outage.
