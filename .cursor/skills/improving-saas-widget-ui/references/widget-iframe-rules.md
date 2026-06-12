# Widget & Iframe Rules

Critical rules for embeddable chat/WhatsApp widgets loaded via iframe on third-party client websites.

## Architecture Overview

```
Client website (host page)
└── <iframe> mounted by embed.js.php
    └── widget.php (widget UI + widget.css + widget.js)
        └── postMessage → parent resizes iframe
```

The host page must remain clickable everywhere except the widget's visible footprint.

## Pointer Events

Inside `widget.php` / `widget.css`:

```css
html, body {
    background: transparent;
    pointer-events: none; /* clicks pass through empty iframe area */
}

.ctcw-container,
.ctcw-widget,
.ctcw-greeting,
.ctcw-hover-box {
    pointer-events: auto; /* widget elements receive clicks */
}
```

In `embed.js.php`, the iframe element itself receives pointer events, but its transparent regions should not block the host page — achieved by transparent body + sized iframe matching content bounds.

## No Shadows on Widget

Enforce globally in `widget.css`:

```css
*, *::before, *::after {
    box-shadow: none !important;
    text-shadow: none !important;
}

/* Also check inline styles and JS that set boxShadow */
```

In embed script, set explicitly:

```javascript
iframe.style.boxShadow = 'none';
```

Remove any `filter: drop-shadow(...)` on widget elements.

## Iframe Sizing Rules

| Scenario | Wrong | Right |
|----------|-------|-------|
| Icon-only button (style-1) | — | ~120×120 minimum |
| Button with text label | Fixed 90×90 | Width ≥ 280px or dynamic |
| Greeting popup open | Fixed small iframe | Minimum ~400×320, dynamic resize |
| Hover tooltip visible | Static size | `reportSize()` includes hover box rect |

### Never do

- Full-screen iframe (`width: 100vw; height: 100vh`)
- Fixed 90px height when CTA text or greeting is enabled
- Hard-coded size that ignores `postMessage` updates

### Always do

- Listen for resize messages from widget iframe
- Clamp size to viewport with small margin (e.g. 16px)
- Re-send viewport dimensions on window resize
- Use different minimums per state (`normal`, `greeting`)

## postMessage Protocol

Widget inside iframe sends:

```javascript
window.parent.postMessage({
    type: 'ctcw',           // or 'ctcw:size' in some implementations
    id: String(widgetId),
    state: 'normal' | 'greeting',
    width: 320,
    height: 120
}, '*');
```

Parent embed script listens:

```javascript
window.addEventListener('message', function (event) {
    if (!iframe || event.source !== iframe.contentWindow) return;
    if (!event.data || event.data.type !== 'ctcw') return;
    if (String(event.data.id) !== config.widgetId) return;
    applySize(event.data.width, event.data.height, event.data.state || 'normal');
});
```

### When clipping occurs, check

1. `reportSize()` measures all visible rects (container, greeting, hover box)
2. `sizeForState('greeting')` returns adequate minimums
3. `minimumForState('greeting')` in embed script matches widget needs
4. Iframe `overflow: hidden` is intentional — fix size, not overflow
5. CSS transforms do not push content outside measured bounds

## Custom CSS Load Order

In `widget.php`, order must be:

```html
<link rel="stylesheet" href="assets/css/widget.css">
<style>/* inline position overrides */</style>
<?php if (!empty($widget['custom_css'])): ?>
<style><?= $widget['custom_css'] ?></style>
<?php endif; ?>
```

Custom CSS **after** default CSS so user overrides win without `!important` spam.

## Embed Code Section (Dashboard)

The snippet shown to users should:

- Use a clean monospace textarea (see `ui-patterns.md`)
- Include only the iframe + script tag — no inline styles with shadows
- Warn about domain lock with a bordered alert, not a modal

Preview iframe on dashboard:

```css
.preview-frame-wrap {
    border: 1px solid var(--line);
    border-radius: 14px;
    overflow: hidden;
    background: #f8fafc;
    box-shadow: none;
}

.preview-frame {
    width: 100%;
    min-height: 420px;
    border: 0;
}
```

## Viewport Sync

Parent should notify iframe of viewport changes:

```javascript
iframe.contentWindow.postMessage({
    type: 'ctcw:viewport',
    width: window.innerWidth,
    height: window.innerHeight
}, '*');
```

Send on: iframe load (with short delays), window resize.

## Mobile vs Desktop

Embed config often carries separate desktop/mobile position and size. On resize crossing breakpoint (~767px):

- Swap iframe `src` mode param or re-apply position settings
- Re-report widget size for active mode
- Do not leave stale dimensions from previous mode
