ALTER TABLE widgets
    ADD COLUMN IF NOT EXISTS greeting_phone_submit_button_id VARCHAR(80) NULL DEFAULT NULL AFTER greeting_submit_text;
