#!/bin/bash
# Stop: end-of-turn quality reminder.
changes=$(git status --porcelain 2>/dev/null)
[ -z "$changes" ] && exit 0

count=$(echo "$changes" | wc -l | tr -d ' ')
echo "WARNING: $count uncommitted file(s) — consider committing before stopping"
echo "$changes" | head -5
[ "$count" -gt 5 ] && echo "  ... and $((count - 5)) more"

# Nudge the gate only when PHP changed
if echo "$changes" | grep -qE '\.php$'; then
  echo "REMINDER: run 'composer fix && composer test' before committing (githooks/pre-commit runs it anyway)."
fi

exit 0
