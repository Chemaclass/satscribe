---
description: Blade, Alpine.js, Tailwind v4, Vite, Lucide icons, Nostr browser auth
globs:
  - "resources/**"
  - "vite.config.js"
  - "tailwind.config.js"
  - "package.json"
---

# Frontend

Blade + Alpine.js + Tailwind v4, bundled by Vite. No React, no build-time framework.

## Commands

```bash
composer dev     # server + queue + logs + vite together
npm run dev      # vite only
npm run build    # production assets
```

## Blade

- Views in `resources/views/`, components in `resources/views/components/`
- Escape by default: `{{ }}`. Use `{!! !!}` only for content you sanitized yourself — AI output is **never** trusted raw
- Extract a component once markup repeats twice — not before
- No business logic in templates. A view receives a ViewModel (`modules/*/Domain/ViewModel/`) or plain data, never a repository

## Alpine.js

- Small, declarative state colocated in the markup: `x-data`, `x-show`, `x-on`
- Anything beyond ~20 lines of inline JS moves into a module under `resources/js/`
- Streaming chat updates append to the DOM incrementally — don't re-render the whole thread

## Tailwind v4

- Utility classes in markup. No custom CSS unless a utility genuinely can't express it
- Config in `tailwind.config.js`; v4 uses the Vite plugin (`@tailwindcss/vite`), not PostCSS
- Dark mode must work in both directions — check both themes before calling a UI change done

## Icons

Use `lucide` (already a dependency). No inline SVG pasted into templates.

## Nostr auth

- `nostr-tools` in the browser; signing happens via the user's NIP-07 extension
- **Never** handle, request, or store a private key. The extension signs; the app verifies
- The server verifies signatures in `modules/Nostr/` — never trust a client-asserted npub

## Debugging CSS

Trace the cascade before patching. Check global selectors (`a:hover`, `*`, the Tailwind base layer) first. No `brightness()` or `color-mix()` tweaks before the actual rule is found.

## Never

- Inline `<style>` blocks or `!important` to win a specificity fight — fix the selector
- Fetching directly from a Blade template
- Rendering AI or user text unescaped
- Committing `public/build/` artifacts
