# File Discovery Guide

Use this reference when the project layout does not match the default PHP SaaS structure.

## Search Commands

Run targeted searches before editing:

```bash
# Dashboard and admin pages
rg -l "dashboard|require_login|settings-card" --glob "*.php"

# Widget renderer and preview
rg -l "widget\.php|ctcw-|postMessage" --glob "*.{php,js,css}"

# Embed generator
rg -l "embed|iframe|postMessage" --glob "*.{php,js}"

# Stylesheets
find . -name "*.css" -not -path "*/node_modules/*"

# Custom CSS injection points
rg -l "custom_css|custom_script" --glob "*.php"
```

## File Role Matrix

| If you need to… | Look for… |
|-----------------|-----------|
| Change page chrome (nav, footer) | `includes/header.php`, `includes/footer.php`, layout partials |
| Change dashboard list/table | `dashboard.php`, `assets/css/style.css` |
| Change login/register look | `login.php`, `register.php`, shared auth CSS |
| Change widget appearance | `widget.php`, `assets/css/widget.css`, `assets/js/widget.js` |
| Fix iframe size on client site | `embed.js.php`, `assets/js/widget.js` |
| Change embed snippet UI | `embed-code.php`, `includes/widget-form.php` |
| Change widget settings form | `includes/widget-form.php`, `create-widget.php`, `edit-widget.php` |
| Change shared PHP helpers | `includes/functions.php` — **read only; avoid UI-unrelated edits** |

## Common Alternate Layouts

### Monorepo / subfolder app

```
apps/chat-manager/
├── public/widget.php
└── resources/views/dashboard.php
```

Scope searches to the app subdirectory. CSS may live in `public/assets/` or `resources/css/`.

### WordPress-style plugin

```
my-plugin/
├── admin/dashboard.php
├── public/widget.php
└── assets/css/admin.css
```

Admin UI uses `admin.css`; frontend widget uses separate CSS.

### Flat legacy project

All PHP files in root; CSS in `/css/` or inline in templates. Prefer extracting repeated inline styles into one CSS file only if the user approves a larger refactor.

## What Not to Edit for UI Work

| File type | Reason |
|-----------|--------|
| `database.sql`, migrations | Schema changes rarely needed for UI |
| `includes/auth.php` | Auth logic — styling only via CSS on auth templates |
| `config.php` | Environment config — leave untouched |
| Minified vendor CSS (`bootstrap.min.css`) | Override in custom CSS instead of editing vendor files |

## Diagnosis Checklist

Before editing, confirm you found:

- [ ] Main dashboard template(s)
- [ ] Primary dashboard stylesheet
- [ ] Widget renderer (`widget.php` or equivalent)
- [ ] Widget stylesheet (separate from dashboard CSS)
- [ ] Embed script that mounts the iframe on client sites
- [ ] Custom CSS/script textarea markup in widget settings form
- [ ] Embed code copy section

If any item is missing, search alternate names (`chat-widget.php`, `floating-button.js`, `admin.css`).
