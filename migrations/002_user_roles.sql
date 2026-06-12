USE click_to_chat_manager;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role VARCHAR(30) NOT NULL DEFAULT 'client' AFTER password,
    ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'active' AFTER role,
    ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER status,
    ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL AFTER last_login_at;

UPDATE users SET role = 'client' WHERE role IS NULL OR role = '';
UPDATE users SET status = 'active' WHERE status IS NULL OR status = '';

CREATE INDEX IF NOT EXISTS idx_users_role_status ON users (role, status);
