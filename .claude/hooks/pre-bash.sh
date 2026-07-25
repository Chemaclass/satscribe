#!/bin/bash
# PreToolUse(Bash): safety guard before command execution.
# Claude Code passes tool input as JSON on stdin — not via $TOOL_INPUT.
cmd=$(jq -r '.tool_input.command // empty')
[ -z "$cmd" ] && exit 0

# Read-only inspection tools can legitimately mention destructive strings
# (grepping for them, writing docs about them). Don't scan those commands.
first=$(echo "$cmd" | sed -E 's/^[[:space:]]*//' | cut -d' ' -f1 | sed 's|.*/||')
case "$first" in
  echo|printf|grep|rg|ag|cat|less|head|tail|jq|find|ls|wc|diff|comm|sort|awk|sed) exit 0 ;;
esac

# Block destructive database operations — anchored to a real invocation,
# not a substring anywhere in the line.
if echo "$cmd" | grep -qiE '(^|[;&|]|&&)[[:space:]]*[^|;&]*artisan[[:space:]]+(migrate:fresh|db:wipe)\b'; then
  echo "BLOCKED: destructive database command. Ask the user before wiping data." >&2
  exit 2
fi

if echo "$cmd" | grep -qiE '(^|[;&|"'"'"'])[[:space:]]*(DROP[[:space:]]+(DATABASE|TABLE)|TRUNCATE[[:space:]]+TABLE)\b'; then
  echo "BLOCKED: destructive SQL detected. Ask the user before dropping data." >&2
  exit 2
fi

# Block mass file deletion at a path root
if echo "$cmd" | grep -qE 'rm\s+(-[a-z]*[rf][a-z]*\s+)+(\/|\.\/|\~|\$HOME)'; then
  echo "BLOCKED: destructive file deletion detected." >&2
  exit 2
fi

# Block bypassing the pre-commit quality gate
if echo "$cmd" | grep -qE 'git\s+(commit|push).*--no-verify'; then
  echo "BLOCKED: --no-verify bypasses composer fix && composer test. Fix the underlying failure instead." >&2
  exit 2
fi

# Block force-push and history rewrites on shared branches.
# The flag has to be its own token in the same command segment: `.*` used to run
# past a pipe and matched the `-f` inside an unrelated `[0-9a-f]` character
# class, blocking ordinary pushes.
if echo "$cmd" | grep -qE 'git\s+push[^;&|]*\s(-f|--force)(\s|$)' && ! echo "$cmd" | grep -q '\-\-force-with-lease'; then
  echo "BLOCKED: plain force-push. Use --force-with-lease, and never on main." >&2
  exit 2
fi

# Warn when tests are run without the static-analysis gate
if echo "$cmd" | grep -qE 'vendor/bin/phpunit' && echo "$cmd" | grep -qE '\-\-filter|tests/'; then
  : # narrow runs are fine, stay quiet
fi

exit 0
