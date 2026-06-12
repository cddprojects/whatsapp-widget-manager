---
name: improving-saas-widget-ui
description: >
  Improves ugly or AI-looking UI in PHP, HTML, CSS, and JavaScript projects —
  especially SaaS dashboards and embeddable WhatsApp/chat widgets. Use when the
  user asks to polish UI, fix dashboard styling, improve forms/cards/buttons,
  clean up embed code sections, fix widget iframe clipping, remove shadows or
  generic Bootstrap/AI gradients, or make a widget/dashboard look more premium.
  Applies to dashboard.php, widget.php, embed.js.php, style.css, widget.css,
  and related PHP includes. Preserves backend and database logic.
paths:
  - "**/*.php"
  - "**/*.css"
  - "**/*.js"
  - "**/*.html"
---

# Improving SaaS & Widget UI

Transform generic or AI-generated UI into clean, professional SaaS surfaces and shadow-free embed widgets — without breaking PHP logic, auth, or database behavior.

## When to Activate

Use this skill when the user mentions any of:

- Dashboard, admin panel, or settings page looks ugly, generic, or "AI-generated"
- Widget UI, embed code, iframe, or WhatsApp button styling
- Spacing, typography, forms, cards, or buttons need polish
- Removing shadows, gradients, or Bootstrap-looking defaults
- Iframe clipping, fixed iframe height, or postMessage resize issues
- Custom CSS/script editor areas that look messy
- Keeping the client website clickable behind a floating widget

Do **not** use for backend-only work, database migrations, auth logic, or API design unless UI files are also in scope.

## Core Principles

1. **Inspect before editing** — map the project structure and diagnose UI/iframe issues first.
2. **Minimal diffs** — edit only necessary files; never rebuild the whole project.
3. **Preserve logic** — keep PHP, SQL, auth, CSRF, and business rules untouched.
4. **No class renames** — improve styling via existing selectors and CSS variables.
5. **Confirm large changes** — ask before restructuring layouts or touching many files.
6. **Subtle over flashy** — borders beat shadows; solid colors beat gradients.

## Workflow

Follow these steps in order.

### Step 1: Inspect project structure

Scan the repo root and common folders:

```
/
├── dashboard.php, login.php, register.php
├── create-*.php, edit-*.php, embed-code.php
├── widget.php, widget-preview.php
├── embed.js.php
├── includes/          (header, footer, auth, forms)
├── assets/css/        (style.css, widget.css)
├── assets/js/         (dashboard.js, widget.js)
└── config.php
```

Use glob/search for: `*dashboard*`, `*widget*`, `*embed*`, `style.css`, `widget.css`.

Load `references/file-discovery.md` when the layout is non-standard.

### Step 2: Locate key files

| Purpose | Typical paths |
|---------|---------------|
| Dashboard / admin | `dashboard.php`, `includes/header.php`, `includes/footer.php` |
| Auth pages | `login.php`, `register.php` |
| Widget renderer | `widget.php` |
| Embed generator | `embed.js.php`, `embed-code.php` |
| Widget form / settings | `includes/widget-form.php`, `edit-widget.php`, `create-widget.php` |
| Dashboard CSS | `assets/css/style.css` |
| Widget CSS | `assets/css/widget.css` |
| Widget JS (postMessage) | `assets/js/widget.js` |
| Embed JS (iframe mount) | `embed.js.php` |

### Step 3: Diagnose before editing

Read relevant files and note:

**Dashboard / SaaS UI issues**
- Heavy `box-shadow` on cards, nav, or buttons
- Purple/blue AI gradients on backgrounds or CTAs
- Default Bootstrap spacing, colors, or component feel
- Cramped forms, misaligned labels, inconsistent radii
- Generic system fonts without hierarchy

**Widget / iframe issues**
- Widget clipped because iframe is too small (e.g. fixed 90×90 when button has text or greeting is open)
- Full-screen or oversized iframe blocking page clicks
- Shadows on widget, greeting popup, or iframe element
- Custom CSS loaded **before** default widget CSS (overrides won't work)
- `pointer-events` blocking clicks on the host page outside the widget

**Embed / code editor issues**
- Raw `<textarea>` with no monospace styling for embed snippets
- Cluttered copy-code sections without clear hierarchy

Document findings briefly before making changes.

### Step 4: Confirm scope for large changes

Ask the user before:

- Restructuring HTML in multiple PHP templates
- Changing more than 3–4 files
- Altering shared includes that affect every page
- Any database schema change (almost never needed for UI work)

Small, targeted CSS and markup tweaks do not require confirmation.

### Step 5: Apply design system

Use the tokens in `references/design-tokens.md`. Summary:

| Token | Value |
|-------|-------|
| Background | `#f8fafc` |
| Cards | `#ffffff` |
| Text | `#0f172a` |
| Muted text | `#64748b` |
| Border | `#e5e7eb` |
| Primary | `#2563eb` |
| WhatsApp green | `#25D366` |
| Border radius | `14px`–`18px` |

**Do:** subtle 1px borders, generous padding, clear type scale, soft primary tints for badges.
**Don't:** large shadows, neon gradients, glassmorphism, Bootstrap defaults, Inter + purple gradient hero clichés.

Prefer CSS custom properties in `:root` and update existing variables rather than scattering hex values.

### Step 6: Improve dashboard UI

Focus areas (see `references/ui-patterns.md`):

1. **Layout** — max-width content area, consistent section spacing (24–32px gaps)
2. **Typography** — page title 28–32px/700, section headings 20–22px/600, body 15–16px, muted helper text
3. **Forms** — labels above inputs, 12–14px input padding, focus ring via border + light tint (not heavy shadow)
4. **Cards** — white background, 1px `#e5e7eb` border, 16–20px padding, 14–16px radius, **no card shadow**
5. **Buttons** — primary solid `#2563eb`, secondary outline or soft fill; 12–16px horizontal padding; no drop shadow on hover
6. **Tables** — light row borders, compact action buttons, status pills with soft background tints
7. **Empty states** — centered, muted copy, single primary CTA

Edit `assets/css/style.css` and minimal markup in PHP templates only where structure blocks good layout.

### Step 7: Improve widget UI

Rules for `assets/css/widget.css` and `widget.php`:

1. **No shadows anywhere** — enforce on widget, button, greeting, hover box:
   ```css
   .ctcw-widget,
   .ctcw-greeting,
   .ctcw-hover-box,
   body, body * {
       box-shadow: none !important;
       text-shadow: none !important;
       filter: none !important; /* prevents drop-shadow via filter */
   }
   ```
2. **WhatsApp green** — `#25D366` for the main button; white text; 14–16px radius
3. **Transparent iframe body** — `background: transparent` on html/body inside widget
4. **Pointer events** — host page stays clickable:
   - iframe container: only the widget footprint receives clicks
   - use `pointer-events: none` on html/body, `pointer-events: auto` on `.ctcw-container` / widget elements
5. **Custom CSS order** — in `widget.php`, custom CSS `<style>` block must come **after** default widget CSS so user overrides work

### Step 8: Fix iframe sizing & clipping

Load `references/widget-iframe-rules.md` for full detail. Critical rules:

| Rule | Why |
|------|-----|
| Never use full-screen iframe | Blocks entire client site |
| Never hard-code 90×90 when style has text or greeting | Content gets clipped |
| Use postMessage resize | Parent iframe grows with widget content |
| Greeting state needs larger minimum | e.g. 400×320 minimum in embed script |
| Re-report size on state change | greeting open/close, hover box, style switch |

Typical postMessage flow:

```
widget.js (inside iframe)  →  postMessage { type: 'ctcw', width, height, state }
embed.js.php (parent page) →  listens, sets iframe.style.width/height
```

If clipping persists, verify `reportSize()` / `sizeForState()` minimums match visible content and that embed listener handles `state: 'greeting'`.

### Step 9: Clean embed code & custom CSS editor

**Embed code section** (`embed-code.php`, widget form embed panel):

- Monospace textarea with light `#f8fafc` background and `#e5e7eb` border
- Copy button aligned to panel heading
- Short helper text; domain-lock warning styled as soft amber alert (border, not shadow)
- Preview iframe in a bordered container, not a floating shadow box

**Custom CSS editor** (widget form custom-code panel):

- `.code-editor` with line numbers or gutter, dark-on-light or soft gray background
- `font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace`
- Adequate `min-height` and `resize: vertical` on textarea
- Clear section title and trust warning; no decorative gradients

### Step 10: Verify & summarize

After edits:

1. Re-read changed files — no PHP logic altered, no accidental SQL changes
2. Check widget CSS for any remaining `box-shadow`, `text-shadow`, or `filter: drop-shadow`
3. Confirm custom CSS load order in `widget.php`
4. List every file changed and what was fixed

## Hard Rules (Never Break)

- Do not rebuild the whole project
- Do not randomly rename CSS classes or PHP variables
- Do not break login, register, dashboard, or CSRF flows
- Do not change database schema unless absolutely required (UI work should not need this)
- Do not add large shadows to dashboard cards, nav, or widget
- Do not use full-screen iframe embeds
- Do not use fixed 90px iframe when the widget shows text labels or greeting popups
- Do not block clicks on the client website outside the widget hit area
- Custom CSS must load after default widget CSS

## Reference Files

Load these on demand — do not paste entire contents into edits:

| File | Use when |
|------|----------|
| `references/design-tokens.md` | Applying colors, spacing, typography |
| `references/file-discovery.md` | Non-standard project layout |
| `references/ui-patterns.md` | Dashboard forms, cards, buttons, tables |
| `references/widget-iframe-rules.md` | Iframe sizing, postMessage, pointer-events |
| `references/ui-antipatterns.md` | Checking for AI/generic UI mistakes |

## Output Format

End every UI improvement task with:

```markdown
## Summary

### Files changed
- `path/to/file` — brief description

### UI fixes
- ...

### Widget / iframe fixes (if applicable)
- ...

### Not changed (preserved)
- PHP logic, auth, database schema, class names
```
