# Design Tokens

Use these tokens consistently across dashboard CSS (`style.css`) and widget CSS (`widget.css`). Prefer updating `:root` variables over inline hex values.

## Color Palette

```css
:root {
    /* Surfaces */
    --bg: #f8fafc;
    --card: #ffffff;
    --text: #0f172a;
    --muted: #64748b;
    --line: #e5e7eb;

    /* Brand */
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-soft: #dbeafe;

    /* WhatsApp widget */
    --green: #25D366;
    --green-dark: #128c7e;

    /* Semantic */
    --danger: #dc2626;
    --danger-soft: #fee2e2;
    --success: #047857;
    --success-soft: #d1fae5;
    --warning: #92400e;
    --warning-soft: #fef3c7;

    /* Elevation — use borders, not shadows */
    --shadow: none;
    --focus-ring: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
```

## Typography

| Role | Size | Weight | Color |
|------|------|--------|-------|
| Page title | 28–32px | 700 | `--text` |
| Section heading | 20–22px | 600 | `--text` |
| Card title | 16–18px | 600 | `--text` |
| Body | 15–16px | 400 | `--text` |
| Helper / label hint | 13–14px | 400 | `--muted` |
| Eyebrow / overline | 12–13px | 600 | `--muted`, uppercase optional |

**Font stack (dashboard):**
```css
font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
```

**Font stack (widget — keep compact):**
```css
font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
```

## Spacing Scale

Use multiples of 4px:

| Token | Value | Usage |
|-------|-------|-------|
| xs | 4px | Tight inline gaps |
| sm | 8px | Label-to-input gap |
| md | 12–16px | Input padding, button padding |
| lg | 20–24px | Card padding |
| xl | 28–32px | Section gaps |
| 2xl | 40–48px | Page hero padding |

## Border Radius

| Element | Radius |
|---------|--------|
| Cards, panels | 14–16px |
| Large hero sections | 16–18px |
| Inputs, buttons | 12–14px |
| Pills, badges | 999px or 10px |
| Code blocks | 10–12px |

## Borders (Preferred Over Shadows)

```css
.card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    box-shadow: none;
}
```

Focus states use a thin ring, not a heavy glow:

```css
input:focus,
select:focus,
textarea:focus {
    border-color: var(--primary);
    box-shadow: var(--focus-ring);
    outline: none;
}
```

## Widget-Specific Tokens

```css
:root {
    --ctcw-green: #25D366;
    --ctcw-green-dark: #128c7e;
    --ctcw-text: #0f172a;
    --ctcw-shadow: none;
}
```

Widget elements must never reintroduce shadow variables. If a legacy `--shadow` exists in dashboard CSS, do not copy it into widget CSS.
