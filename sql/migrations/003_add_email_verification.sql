-- 003 add email verification fields

ALTER TABLE users
  ADD COLUMN email_verify_token VARCHAR(255) DEFAULT NULL,
  ADD COLUMN email_verified_at DATETIME DEFAULT NULL;
