ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS greeting_open_behavior VARCHAR(30) NOT NULL DEFAULT 'auto_delay' AFTER greeting_delay_seconds;
