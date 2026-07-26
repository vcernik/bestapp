ALTER TABLE admin_user
	ADD COLUMN role VARCHAR(40) NOT NULL DEFAULT 'superadmin' AFTER name,
	ADD KEY idx_admin_user_role (role);
