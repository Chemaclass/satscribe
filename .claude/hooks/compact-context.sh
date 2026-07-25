#!/bin/bash
# SessionStart(compact): re-inject only what compaction most often drops.
# Keep this SHORT — the full ruleset lives in CLAUDE.md and .claude/rules/,
# which stay available. Duplicating them here just burns context twice.
cat <<'EOF'
## Post-compaction reminder

**Satscribe** — Laravel, business logic in `modules/`, hexagonal: Infrastructure → Application → Domain.

- Repositories are the only Eloquent gateway. Interface in `Domain/`, impl in `Application/`/`Infrastructure/`, bound in `<Module>ServiceProvider::$singletons`.
- TDD: failing test first. `mock()`, never `Mockery::mock()`.
- Gate: `composer test`. Format: `composer fix` (PHP edits auto-format on write).
- Commits: conventional, `ref:` not `refactor:`, never mention AI.

Full rules: `CLAUDE.md` → `.claude/rules/{architecture,php,testing,laravel,error-handling,frontend,commits}.md`
EOF
