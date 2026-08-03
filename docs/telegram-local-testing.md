# Telegram Channel — Local Testing (Windows PowerShell)

GitHub changes do **not** affect production until you manually merge and deploy.

## 1. Fetch and check out the feature branch

```powershell
cd "C:\CDD Tech\ctc-local"
git fetch origin
git checkout feature/telegram-channel
git pull origin feature/telegram-channel
```

## 2. Start MariaDB

1. Start Docker Desktop.
2. Start the `ctc-mariadb` container.
3. Confirm port `3307` is listening:

```powershell
Test-NetConnection 127.0.0.1 -Port 3307
```

## 3. Confirm local `.env` (do not commit it)

Ensure `.env` contains local DB settings such as:

- `DB_HOST=127.0.0.1`
- `DB_PORT=3307`
- `DB_NAME=click_to_chat_manager`
- `DB_USER=ctc_local`
- `DB_PASS=...` (your local password)

Do not commit `.env`.

## 4. Back up the local database

```powershell
docker exec ctc-mariadb mysqldump -u ctc_local -p click_to_chat_manager > "C:\CDD Tech\ctc-local\backup-before-telegram.sql"
Get-Item "C:\CDD Tech\ctc-local\backup-before-telegram.sql"
```

Confirm the backup file is non-empty before continuing.

## 5. Baseline historical migrations (existing DB imported from database.sql)

If this database was created from `database.sql` and migrations were never tracked:

```powershell
cd "C:\CDD Tech\ctc-local"
php migrate.php --status
php migrate.php --baseline
php migrate.php --status
```

Baseline verifies the **full expected state** of each historical migration before recording it. Ambiguous/partial states are reported and nothing is guessed.

## 6. Run pending Telegram migrations

```powershell
php migrate.php
php migrate.php
php migrate.php --status
```

The second run must safely skip completed migrations.

## 7. Verify new tables/columns

```powershell
docker exec -it ctc-mariadb mysql -u ctc_local -p click_to_chat_manager -e "SHOW TABLES LIKE 'widget_channels'; SHOW TABLES LIKE 'channel_destinations'; SHOW COLUMNS FROM widget_leads LIKE 'channel'; SELECT filename FROM schema_migrations ORDER BY filename;"
```

## 8. Start PHP

```powershell
cd "C:\CDD Tech\ctc-local"
php -S 127.0.0.1:8000 dev-router.php
```

Open: http://127.0.0.1:8000

## 9. Configure a Telegram widget (Superadmin)

1. Log in as Superadmin.
2. Edit a widget.
3. Open **Communication Channels**.
4. Keep **WhatsApp only** first and confirm existing WhatsApp still works.
5. Add at least one Telegram destination under **Destinations → Telegram**.
6. Switch mode to **Telegram only** or **WhatsApp + Telegram**.
7. Save.
8. Use **Test Telegram Link** (opens server-built URL).

## 10. Visitor tests

### WhatsApp-only

Embed/preview should behave exactly as before.

### Telegram-only

1. Open the widget.
2. Complete the existing lead phone form.
3. Continue on Telegram.
4. Confirm lead is saved with channel `telegram`.

### Multi-channel

1. Submit the lead form.
2. Choose WhatsApp or Telegram.
3. Confirm only that channel’s destinations are used.

### Destination types

Test username, bot (+ optional start param), group link, and channel link.

## 11. Reports / CSV / API

- Lead reports: filter by All / WhatsApp / Telegram; confirm badges.
- CSV export: confirm channel columns and full filtered export (not just current page).
- API (Postman):

```http
GET http://127.0.0.1:8000/api/v1/leads/summary?period=today
Authorization: Bearer <client_api_key>
X-Widget-API-Key: <widget_api_key>
```

```http
GET http://127.0.0.1:8000/api/v1/leads/summary?period=yesterday&channel=telegram
```

```http
GET http://127.0.0.1:8000/api/v1/leads/summary?period=today&channel=invalid
```

Invalid channel must return HTTP 400.

## 12. CLI tests

```powershell
php -l migrate.php
php cli/test_telegram_channel.php
```

## 13. Restore the local backup (if needed)

```powershell
Get-Content "C:\CDD Tech\ctc-local\backup-before-telegram.sql" | docker exec -i ctc-mariadb mysql -u ctc_local -p click_to_chat_manager
```
