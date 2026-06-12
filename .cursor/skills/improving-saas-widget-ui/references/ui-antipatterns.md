# UI Anti-Patterns to Remove

Scan changed files for these common "AI-generated" or low-quality UI signals and replace them with the design tokens.

## Visual Anti-Patterns

| Anti-pattern | Fix |
|--------------|-----|
| `box-shadow: 0 20px 60px rgba(...)` on cards | `border: 1px solid #e5e7eb; box-shadow: none` |
| Purple-to-blue gradient backgrounds | Solid `#f8fafc` or white cards on light gray |
| Gradient text on headings | Solid `#0f172a` with weight/size hierarchy |
| Glassmorphism (`backdrop-filter: blur`) | Opaque white cards with border |
| Neon glow on buttons | Solid primary color, darker hover |
| `--shadow` variable used on every component | Set `--shadow: none`, use borders |
| Bootstrap default blue `#007bff` | Use `#2563eb` primary token |
| Rounded corners mixed (4px, 8px, 24px randomly) | Standardize to 12–16px inputs, 14–18px cards |

## Layout Anti-Patterns

| Anti-pattern | Fix |
|--------------|-----|
| Edge-to-edge content with no max-width | `max-width: 1120px; margin: 0 auto` |
| Labels beside inputs on narrow forms | Stack label above input |
| Buttons different heights in same row | Shared `.btn` padding |
| Tables without horizontal scroll on mobile | `.table-wrap { overflow-x: auto }` |
| Cramped 8px card padding | 20–24px card padding |

## Widget Anti-Patterns

| Anti-pattern | Fix |
|--------------|-----|
| Drop shadow on WhatsApp button | `box-shadow: none !important` |
| Full-screen invisible iframe overlay | Size iframe to content via postMessage |
| Fixed 90×90 iframe with text button | Dynamic width ≥ 280px |
| Widget blocks entire page clicks | `pointer-events: none` on body |
| Custom CSS before default widget CSS | Move custom `<style>` after defaults |
| Arial-only widget with no sizing | System UI stack, proper padding/radius |

## Code Editor Anti-Patterns

| Anti-pattern | Fix |
|--------------|-----|
| Plain white textarea for embed code | Monospace `.code-editor` with gutter |
| No copy button near embed snippet | Panel heading + copy btn row |
| Tiny 3-row textarea for long iframe code | `rows="6"` minimum, `resize: vertical` |
| Dark gradient wrapper around code block | Light `#f8fafc` background, `#e5e7eb` border |

## PHP / Logic Anti-Patterns (Do Not Introduce)

| Anti-pattern | Why forbidden |
|--------------|---------------|
| Renaming form field names | Breaks save handlers |
| Removing CSRF tokens | Security regression |
| Inline SQL changes for UI | Out of scope |
| Stripping auth checks from templates | Security regression |
| Moving business logic into CSS files | Wrong layer |

## Quick Audit Commands

```bash
# Find heavy shadows in CSS
rg "box-shadow:" assets/ --glob "*.css" | rg -v "none|focus-ring|0 0 0"

# Find gradients
rg "gradient|linear-gradient" assets/ --glob "*.css"

# Find fixed small iframe sizes
rg "90px|80px|100px" embed.js.php assets/js/

# Find pointer-events issues
rg "pointer-events" assets/css/widget.css widget.php
```

After audit, fix only what relates to the user's request — do not drive-by refactor unrelated files.
