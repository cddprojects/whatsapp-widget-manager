-- Optional consent notice + independent Telegram launcher styles.
-- Safe to re-run: ADD COLUMN IF NOT EXISTS.

ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS consent_notice_enabled TINYINT(1) NOT NULL DEFAULT 0
        AFTER greeting_capture_phone,
    ADD COLUMN IF NOT EXISTS consent_notice_text VARCHAR(500) NULL DEFAULT NULL
        AFTER consent_notice_enabled,
    ADD COLUMN IF NOT EXISTS telegram_desktop_style VARCHAR(40) NOT NULL DEFAULT 'style-4'
        AFTER mobile_style,
    ADD COLUMN IF NOT EXISTS telegram_mobile_style VARCHAR(40) NOT NULL DEFAULT 'style-4'
        AFTER telegram_desktop_style;
