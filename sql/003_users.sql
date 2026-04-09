-- Migration 003: add username to existing users table
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS username TEXT UNIQUE;
