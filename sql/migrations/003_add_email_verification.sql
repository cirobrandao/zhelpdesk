-- 003 add email verification fields

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_verify_token VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL;
