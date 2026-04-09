-- Migration 004: add approval status and role to users table

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'denied')),
    ADD COLUMN IF NOT EXISTS role TEXT NOT NULL DEFAULT 'user'
        CHECK (role IN ('user', 'admin'));

-- Approve all pre-existing users so current team members are not locked out
UPDATE users SET status = 'approved' WHERE status = 'pending';

-- To create your first admin account after running this migration, run:
-- UPDATE users SET role = 'admin', status = 'approved' WHERE email = 'your-admin@email.com';
