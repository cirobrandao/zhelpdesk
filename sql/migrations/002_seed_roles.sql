-- 002 seed roles

INSERT INTO roles (name) VALUES ('admin'), ('agent'), ('user')
ON DUPLICATE KEY UPDATE name = name;
