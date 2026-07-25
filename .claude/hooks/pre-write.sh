#!/bin/bash
# PreToolUse(Write|Edit): guard file placement and layer boundaries.
#
# Policy: BLOCK new debt, WARN on existing debt.
# The codebase has accepted patterns (repository interfaces returning Eloquent
# models) and a few known violations. Hard-blocking every touch to those files
# would make them uneditable, so existing files get a warning and new files get
# a block. See .claude/rules/architecture.md
INPUT=$(cat)
file=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')
[ -z "$file" ] && exit 0

content=$(echo "$INPUT" | jq -r '
  (.tool_input.content // "")
  + "\n" + (.tool_input.new_string // "")
  + "\n" + ([.tool_input.edits[]?.new_string] | join("\n"))
')

# A file already on disk is existing code; anything else is new.
if [ -f "$file" ]; then is_new=0; else is_new=1; fi

report() { # severity_msg
  if [ "$is_new" = "1" ]; then
    echo "BLOCKED: $1" >&2
    echo "See .claude/rules/architecture.md" >&2
    exit 2
  fi
  echo "WARNING (existing file, not blocked): $1" >&2
}

# --- Placement: business logic belongs in modules/, not app/ -----------------

if echo "$file" | grep -qE '(^|/)app/(Services|Actions|Repositories|Http/Controllers|Domain|UseCase)/.*\.php$'; then
  echo "BLOCKED: business logic must live in modules/<Module>/, not app/." >&2
  echo "app/ holds Laravel glue only (Models, framework providers)." >&2
  exit 2
fi

# --- Domain layer ------------------------------------------------------------

if echo "$file" | grep -qE '(^|/)modules/[A-Za-z]+/Domain/.*\.php$'; then

  # Hard rule, zero violations today: Domain never reaches into Infrastructure.
  if echo "$content" | grep -qE '^use Modules\\[A-Za-z]+\\Infrastructure'; then
    echo "BLOCKED: Domain imports Infrastructure in $file." >&2
    echo "Declare the interface in Domain, implement it in Infrastructure, bind it in the ModuleServiceProvider." >&2
    exit 2
  fi

  # Accepted today: Collection, Paginator, Http\Client contracts, App\Models in
  # repository interfaces. Anything heavier is framework leakage.
  if echo "$content" | grep -qE '^use Illuminate\\(Database|Foundation|Routing|Console|Queue|Mail|Notifications|Session|Cache|Auth)\\'; then
    report "Domain imports a Laravel runtime component in $file. Domain may reference Collection/Paginator/Http-client contracts only."
  fi

  if echo "$content" | grep -qE '^use Illuminate\\Support\\Facades\\'; then
    report "Domain uses a Laravel facade in $file. Inject the contract instead."
  fi
fi

# --- Eloquent confinement ----------------------------------------------------

if echo "$file" | grep -qE '(^|/)modules/[A-Za-z]+/(Application|Infrastructure/Http)/.*\.php$'; then
  if echo "$content" | grep -qE '::(where|find|create|firstOrCreate|updateOrCreate|all)\(|DB::'; then
    report "Eloquent/DB query in $file. Repositories are the only DB gateway — move it into Infrastructure/Repository/ and inject the interface."
  fi
fi

exit 0
