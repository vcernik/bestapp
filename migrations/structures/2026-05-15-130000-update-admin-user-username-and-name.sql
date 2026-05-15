ALTER TABLE admin_user
	DROP INDEX uq_admin_user_email,
	CHANGE COLUMN email username VARCHAR(255) NOT NULL;

ALTER TABLE admin_user
	ADD COLUMN name VARCHAR(255) NULL AFTER username;

UPDATE admin_user
SET name = username
WHERE name IS NULL OR name = '';

ALTER TABLE admin_user
	MODIFY COLUMN name VARCHAR(255) NOT NULL,
	ADD UNIQUE KEY uq_admin_user_username (username);
