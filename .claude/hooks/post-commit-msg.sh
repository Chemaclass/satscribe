#!/bin/bash
# PostToolUse(Bash): validate the commit message after `git commit`.
cmd=$(jq -r '.tool_input.command // empty')

# Only a real `git commit` invocation — not a mention inside a heredoc, echo, or test payload
echo "$cmd" | grep -qE '(^|[;&|]|&&)\s*git\s+commit\b' || exit 0

# Only judge a commit this command actually just created. Without this, any command
# mentioning `git commit` re-validates old (possibly pushed) history and blocks spuriously.
recent=$(git log -1 --since='2 minutes ago' --pretty=%H 2>/dev/null)
[ -z "$recent" ] && exit 0

msg=$(git log -1 --pretty=%B 2>/dev/null)
[ -z "$msg" ] && exit 0

# Block AI *attribution* — never allowed in this repo's history.
# Path references like `.claude/` or `~/.claude` are legitimate (this repo
# versions a .claude/ config dir), so strip those before matching.
# NOTE: no \b — BSD sed (macOS) does not support word boundaries.
attribution=$(echo "$msg" \
  | sed -E 's#[~./[:alnum:]_-]*/?\.claude(/[^[:space:]]*)?##g' \
  | sed -E 's#CLAUDE\.md##g' \
  | sed -E 's#[Cc]laude\.ai(/[^[:space:]]*)?##g')

if echo "$attribution" | grep -qiE 'co-authored-by:.*(claude|anthropic|\bai\b)|generated with.*(claude|ai)|(^|[[:space:]])(claude|anthropic)([[:space:]]|[.,!]|$)'; then
  echo "BLOCKED: commit message contains AI attribution. Amend it before pushing:" >&2
  echo "  git commit --amend" >&2
  echo "Offending text: $(echo "$attribution" | grep -ioE 'co-authored-by:.*|generated with.*|(claude|anthropic)' | head -3 | tr '\n' ' ')" >&2
  exit 2
fi

first_line=$(echo "$msg" | head -1)

# Conventional commit format — scope optional
if ! echo "$first_line" | grep -qE '^(feat|fix|ref|style|docs|test|chore|perf|ci|build|revert)(\(.+\))?: .+$'; then
  echo "WARNING: not a conventional commit. Expected <type>(<scope>): <description>" >&2
  echo "Got: $first_line" >&2
fi

# `refactor:` is not used in this project
if echo "$first_line" | grep -qE '^refactor(\(.+\))?:'; then
  echo "WARNING: use 'ref:' instead of 'refactor:' in this project." >&2
fi

if [ ${#first_line} -gt 80 ]; then
  echo "WARNING: subject is ${#first_line} chars (max 80)." >&2
fi

if echo "$first_line" | grep -qE '[[:alpha:]]\.$'; then
  echo "WARNING: subject ends with a period — drop it." >&2
fi

exit 0
