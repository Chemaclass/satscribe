#!/bin/bash
# PostToolUse(Write|Edit): auto-format the touched file.
file=$(jq -r '.tool_input.file_path // empty')
[ -z "$file" ] && exit 0
[ -f "$file" ] || exit 0

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$PROJECT_DIR" 2>/dev/null || exit 0

case "$file" in
  *.php) ;;
  *) exit 0 ;;
esac

# Passing an explicit path to php-cs-fixer OVERRIDES the Finder in
# .php-cs-fixer.php, which deliberately scopes formatting to app/, modules/
# and tests/. Without this guard the hook reformats config/, routes/,
# database/ and bootstrap/ too — e.g. it added declare(strict_types=1) to
# config/services.php, the only config file that had it.
rel="${file#"$PROJECT_DIR"/}"
case "$rel" in
  app/*|modules/*|tests/*) ;;
  *) exit 0 ;;
esac

[ -x ./vendor/bin/php-cs-fixer ] && ./vendor/bin/php-cs-fixer fix --quiet "$file" 2>/dev/null

exit 0
