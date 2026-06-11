# Click To Chat Manager

A standalone PHP 8 + MySQL "Click to WhatsApp Chat" manager. Clients can register, log in, create multiple domain-specific WhatsApp widgets, preview them, and copy iframe embed code for any HTML website.

## Features

- Client registration, login, logout, password hashing, protected dashboard pages.
- PDO database access with prepared statements.
- Each client can manage multiple widgets/domains.
- Public iframe renderer with widget ID + public key access.
- Domain normalization and referrer-based domain validation when available.
- Random WhatsApp number rotation.
- Pre-filled message variables: `{site}`, `{title}`, `{url}`, `{url_full}`.
- Separate desktop/mobile styles, positions, display rules, URL structures, and open behavior.
- Business hours with always open, always closed, or custom weekly schedule.
- Greeting popup with delay and close button.
- Isolated custom CSS/head/body/footer code for the iframe widget only.
- Responsive dashboard with clean card-based UI.

## Requirements

- PHP 8.0 or newer with PDO MySQL enabled
- MySQL 5.7+/8.0+ or MariaDB
- A web server such as Apache or Nginx pointing to this directory

## Installation

1. Copy the files to your PHP web root.
2. Create/import the database:

   ```bash
   mysql -u root -p < database.sql
   ```

   The SQL file creates a database named `click_to_chat_manager`, the `users` and `widgets` tables, and default demo data.

3. Configure database credentials and app URL in `config.php`:

   ```php
   define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
   define('DB_NAME', getenv('DB_NAME') ?: 'click_to_chat_manager');
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') ?: '');
   define('SYSTEM_BASE_URL', rtrim(getenv('SYSTEM_BASE_URL') ?: 'http://localhost', '/'));
   ```

   Set `SYSTEM_BASE_URL` to the public URL where the manager is hosted, for example:

   ```php
   define('SYSTEM_BASE_URL', 'https://chat.your-domain.com');
   ```

4. Visit:

   ```text
   https://your-system-domain.com/register.php
   ```

   Or log in with the demo account:

   ```text
   Email: demo@example.com
   Password: password
   ```

5. Create or edit a widget, enter the website domain, save, then copy the generated iframe code from the edit page or `embed-code.php`.

### Updating an existing database

If you already imported an older version of the database, run the domain-lock migration once:

```bash
mysql -u root -p click_to_chat_manager < migrations/001_domain_lock.sql
```

In phpMyAdmin, open the `click_to_chat_manager` database, choose the **Import** tab, and import `migrations/001_domain_lock.sql`.

## Example iframe embed code

```html
<style>
  #ctcw-frame-WIDGET_ID {
    border: 0;
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 380px;
    height: 340px;
    max-width: calc(100vw - 16px);
    max-height: calc(100vh - 16px);
    background: transparent;
    pointer-events: auto;
    z-index: 999999;
  }
</style>
<iframe
  id="ctcw-frame-WIDGET_ID"
  src="https://YOUR-SYSTEM-DOMAIN.com/widget.php?id=WIDGET_ID&key=PUBLIC_KEY"
  scrolling="no"
  style="background:transparent;"
  allowtransparency="true"></iframe>
```

Paste this before the closing `</body>` tag of the client website.

## File structure

```text
config.php
database.sql
register.php
login.php
logout.php
dashboard.php
create-widget.php
edit-widget.php
embed-code.php
widget-preview.php
widget.php
includes/
  auth.php
  functions.php
  header.php
  footer.php
  widget-form.php
assets/
  css/
    style.css
    widget.css
  js/
    dashboard.js
    widget.js
```

## Custom Code section

Each widget has a Custom Code section:

- Custom CSS loads after `assets/css/widget.css`.
- Custom script head is inserted inside `<head>`.
- Custom script body is inserted immediately after `<body>`.
- Custom script foot is inserted before `</body>`.

Only trusted frontend HTML/CSS/JavaScript should be added. PHP tags are stripped from custom script fields, no server-side evaluation is performed, and no file upload is provided.

## Security notes

- Passwords use `password_hash()` and login uses `password_verify()`.
- Dashboard forms include CSRF tokens.
- Widget create/update/delete queries are scoped to the authenticated `user_id`.
- Public widgets require both `id` and `public_key`.
- Domain lock can restrict each public iframe to the registered domain, optional `www`, optional subdomains, and strict missing-referrer checks.
- Normal dashboard text output is escaped with `htmlspecialchars()`.
- Login attempts are rate-limited per session after repeated failures.
