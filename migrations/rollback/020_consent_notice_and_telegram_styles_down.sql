-- Rollback 020: remove consent notice and Telegram style columns.
-- Does not touch WhatsApp desktop_style / mobile_style.

ALTER TABLE widgets
    DROP COLUMN IF EXISTS telegram_mobile_style,
    DROP COLUMN IF EXISTS telegram_desktop_style,
    DROP COLUMN IF EXISTS consent_notice_text,
    DROP COLUMN IF EXISTS consent_notice_enabled;
