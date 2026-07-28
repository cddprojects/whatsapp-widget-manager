# Telegram Channel — Rollback

Use this guide only if a Telegram release must be reversed.

## Warnings

- Destructive Git and database commands can permanently remove data.
- Rolling back Telegram migrations may remove Telegram destinations and channel metadata on leads.
- Existing WhatsApp phone columns and historical WhatsApp lead phone fields must be preserved.

## A. Code rollback (preferred first step)

1. Record the bad deploy commit:

```bash
cd /var/www/ctc/whatsapp-widget-manager
git rev-parse HEAD
```

2. Return to the known-good pre-deploy commit:

```bash
# WARNING: confirm the commit hash carefully before resetting.
git fetch origin
git checkout <pre-deploy-commit>
# or fast-forward/revert via your normal production process
```

3. Restart PHP-FPM after code rollback.

If the new migrations already ran, code rollback alone is not enough — also restore the database or run migration rollback SQL.

## B. Migration rollback (protect WhatsApp)

Rollback SQL files:

- `migrations/rollback/019_widget_leads_channel_down.sql`
- `migrations/rollback/018_channel_destinations_down.sql`
- `migrations/rollback/017_widget_channels_down.sql`

Run **in reverse order** only after a verified backup:

```bash
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < migrations/rollback/019_widget_leads_channel_down.sql
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < migrations/rollback/018_channel_destinations_down.sql
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < migrations/rollback/017_widget_channels_down.sql
```

Then remove those rows from `schema_migrations` if present:

```sql
DELETE FROM schema_migrations
WHERE filename IN (
  '019_widget_leads_channel.sql',
  '018_channel_destinations.sql',
  '017_widget_channels.sql'
);
```

### What may be lost

- Telegram destinations in `channel_destinations`
- Widget Telegram enablement in `widget_channels`
- Lead channel metadata columns (`channel`, destination snapshot, redirect/fallback timestamps)

### What must remain

- `widgets.whatsapp_*` columns and `random_numbers_json`
- Existing visitor phone fields on leads
- `whatsapp_redirect_url`
- API credentials and historical WhatsApp lead rows

## C. Full database-backup restoration

If migration rollback is unsafe or incomplete:

1. Stop writes if possible.
2. Restore the pre-deploy mysqldump.
3. Verify WhatsApp widgets and lead reports.
4. Redeploy the pre-deploy code commit.
5. Restart PHP-FPM and re-test login + one WhatsApp widget.

## D. After rollback

- Confirm Superadmin/Client WhatsApp destination management still works.
- Confirm public WhatsApp-only widgets still resolve and redirect.
- Do not re-run Telegram migrations until the issue is understood.
