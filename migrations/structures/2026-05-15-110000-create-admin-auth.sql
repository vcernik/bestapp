CREATE TABLE admin_user (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	email VARCHAR(255) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	failed_count INT UNSIGNED NOT NULL DEFAULT 0,
	blocked_until DATETIME NULL,
	last_attempt_at DATETIME NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_admin_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_password_reset (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	token_hash VARCHAR(255) NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_admin_password_reset_user_id (user_id),
	KEY idx_admin_password_reset_expires_at (expires_at),
	CONSTRAINT fk_admin_password_reset_user FOREIGN KEY (user_id) REFERENCES admin_user (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_activity_log (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NULL,
	action VARCHAR(120) NOT NULL,
	payload_json JSON NOT NULL,
	created_at DATETIME(6) NOT NULL,
	PRIMARY KEY (id),
	KEY idx_admin_activity_log_created_at (created_at),
	KEY idx_admin_activity_log_user_id (user_id),
	KEY idx_admin_activity_log_action (action),
	CONSTRAINT fk_admin_activity_log_user FOREIGN KEY (user_id) REFERENCES admin_user (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
