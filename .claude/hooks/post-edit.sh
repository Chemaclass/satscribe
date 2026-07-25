#!/bin/bash
# PostToolUse(Write|Edit): auto-format the touched file.
file=$(jq -r '.tool_input.file_path // empty')
[ -z "$file" ] && exit 0
[ -f "$file" ] || exit 0

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$PROJECT_DIR" 2>/dev/null || exit 0

case "$file" in
  *.php)
    [ -x ./vendor/bin/php-cs-fixer ] && ./vendor/bin/php-cs-fixer fix --quiet "$file" 2>/dev/null
    ;;
esac

exit 0
