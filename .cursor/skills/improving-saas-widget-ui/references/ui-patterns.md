# Dashboard UI Patterns

Apply these patterns when improving SaaS dashboard, settings, and auth pages. Match existing class names where possible — add new utility classes only when necessary.

## Page Shell

```css
.page-shell,
.dashboard-shell {
    max-width: 1120px;
    margin: 0 auto;
    padding: 32px 24px 48px;
}
```

Hero section at top of dashboard:

```css
.dashboard-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 28px;
}

.dashboard-hero h1 {
    margin: 0 0 8px;
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.dashboard-hero p {
    margin: 0;
    color: var(--muted);
    max-width: 52ch;
}
```

## Cards & Panels

Replace heavy shadows with borders:

```css
.settings-card,
.card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 24px;
    box-shadow: none;
}

.card-header-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
```

Remove legacy shadow usage:

```css
/* BEFORE — avoid */
box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);

/* AFTER */
border: 1px solid var(--line);
box-shadow: none;
```

## Forms

```css
label {
    display: grid;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
}

label small {
    font-weight: 400;
    color: var(--muted);
}

input, select, textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: #fff;
    font: inherit;
    color: var(--text);
}

.form-grid {
    display: grid;
    gap: 18px;
}

.form-grid-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

@media (max-width: 768px) {
    .form-grid-2 { grid-template-columns: 1fr; }
}
```

Section titles in multi-step settings forms:

```css
.section-title {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.section-title > span {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--primary-soft);
    color: var(--primary);
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}
```

## Buttons

```css
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 12px;
    border: 1px solid transparent;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    box-shadow: none;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.btn-primary {
    background: var(--primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--primary-dark);
    box-shadow: none; /* never add hover shadow */
}

.btn-light {
    background: #fff;
    border-color: var(--line);
    color: var(--text);
}

.btn-small {
    padding: 8px 12px;
    font-size: 13px;
}
```

## Tables

```css
.table-wrap {
    overflow-x: auto;
    border: 1px solid var(--line);
    border-radius: 14px;
}

.widget-table {
    width: 100%;
    border-collapse: collapse;
}

.widget-table th,
.widget-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid var(--line);
}

.widget-table th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted);
    background: #f8fafc;
}

.widget-table tr:last-child td {
    border-bottom: 0;
}
```

## Status Pills

```css
.status-pill {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: var(--primary-soft);
    color: var(--primary);
}
```

## Alerts (No Shadow)

```css
.alert {
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--line);
    background: #fff;
    box-shadow: none;
}

.alert-warning {
    background: var(--warning-soft);
    border-color: #fcd34d;
    color: var(--warning);
}
```

## Code / Embed Editor

```css
.code-editor {
    display: grid;
    grid-template-columns: auto 1fr;
    border: 1px solid var(--line);
    border-radius: 12px;
    overflow: hidden;
    background: #f8fafc;
}

.code-lines {
    margin: 0;
    padding: 12px 10px;
    background: #f1f5f9;
    color: var(--muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    line-height: 1.6;
    user-select: none;
}

.code-textarea,
.embed-box textarea {
    border: 0;
    border-radius: 0;
    background: #f8fafc;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 13px;
    line-height: 1.6;
    resize: vertical;
    min-height: 140px;
}

.embed-box {
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 20px;
    background: var(--card);
    box-shadow: none;
}

.panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}
```

## Empty State

```css
.empty-state {
    text-align: center;
    padding: 48px 24px;
    border: 1px dashed var(--line);
    border-radius: 16px;
    background: #fafbfc;
}

.empty-state h3 {
    margin: 0 0 8px;
}

.empty-state p {
    margin: 0 0 20px;
}
```
