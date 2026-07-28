# Telegram Channel — Production Deployment

**GitHub changes do not affect production until you manually merge and deploy them.**

Do **not** import `database.sql` into production.

Production path: `/var/www/ctc/whatsapp-widget-manager`  
Production domain: `https://ctc.chatfromforms.com`

## Pre-flight

1. Confirm local testing passed (`docs/telegram-local-testing.md`).
2. Merge the pull request into `main` manually (do not auto-merge).
3. Schedule a maintenance window if needed.
4. Have a recent production database backup tool ready.

## Deploy steps

### 1. SSH to production

```bash
ssh ec2-user@<production-host>
```

### 2. Check git status and record pre-deploy commit

```bash
cd /var/www/ctc/whatsapp-widget-manager
git status
git rev-parse HEAD
git log -1 --oneline
```

Record the commit hash before changing anything.

### 3. Back up the production database

Use your existing MariaDB/MySQL credentials from production `.env` **without printing secret values**.

Example pattern:

```bash
# Load DB settings privately; do not echo passwords.
set -a
source .env
set +a

mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "/home/ec2-user/ctc-backup-$(date +%Y%m%d%H%M%S).sql"
ls -lh /home/ec2-user/ctc-backup-*.sql | tail -1
```

Confirm the backup file is non-empty.

### 4. Pull code (fast-forward only)

```bash
cd /var/www/ctc/whatsapp-widget-manager
git fetch origin
git pull --ff-only origin main
```

### 5. Confirm `.env` permissions

```bash
ls -l .env
# expected owner/group/mode guidance:
# owner: ec2-user
# group: apache
# mode: 640
```

Adjust only if needed:

```bash
sudo chown ec2-user:apache .env
sudo chmod 640 .env
```

### 6. Check required settings without printing secrets

```bash
php -r 'require "includes/env.php"; foreach (["DB_HOST","DB_NAME","DB_USER","SYSTEM_BASE_URL"] as $k) { $v=getenv($k); echo $k."=".( $v===""||$v===false ? "(missing)" : "(set)" ).PHP_EOL; } echo "DB_PORT=".(getenv("DB_PORT") ? "(set)" : "(unset-ok)").PHP_EOL;'
```

Production may omit `DB_PORT`; that remains supported.

### 7. PHP syntax checks

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

### 8. Migration baseline (only when explicitly required)

If production already has the historical schema but no `schema_migrations` tracking:

```bash
php migrate.php --status
php migrate.php --baseline
```

Only baseline when the verifier reports a complete expected state. Do not force-baseline ambiguous installs.

### 9. Run pending migrations

```bash
php migrate.php
php migrate.php
php migrate.php --status
```

The second run must skip completed migrations.

### 10. Restart PHP-FPM

```bash
sudo systemctl restart php-fpm
# or the Amazon Linux service name used on this host
```

### 11. Nginx

```bash
sudo nginx -t
```

Reload Nginx **only if configuration changed**:

```bash
sudo systemctl reload nginx
```

### 12. Smoke tests

1. Log in to the dashboard.
2. Test an existing WhatsApp-only widget (embed + lead + redirect).
3. Configure/test a Telegram widget.
4. Hit public endpoints:
   - `/embed.js.php?id=...&key=...`
   - `/widget.php?id=...&key=...`
   - lead save / destination resolve
5. Test API auth + optional `channel=` filter.
6. Inspect PHP-FPM and Nginx logs for errors.

## Do not

- Import `database.sql` into production
- Commit or print `.env` / Telegram bot tokens / API peppers
- Deploy automatically from GitHub without this checklist
