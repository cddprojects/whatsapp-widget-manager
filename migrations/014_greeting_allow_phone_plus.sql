ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS greeting_allow_phone_plus TINYINT(1) NOT NULL DEFAULT 1 AFTER greeting_phone_required;
