CREATE DATABASE IF NOT EXISTS village_connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE village_connect;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user', 'manager', 'admin') NOT NULL DEFAULT 'user',
  bio TEXT NULL,
  avatar_path VARCHAR(255) NULL,
  last_edited_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_last_edited_by FOREIGN KEY (last_edited_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profile_media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  media_type ENUM('image', 'video', 'audio') NOT NULL,
  title VARCHAR(120) NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_media_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_profile_media_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS complaints (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
  resolved_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_complaints_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_complaints_user (user_id),
  INDEX idx_complaints_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  description TEXT NOT NULL,
  posted_by INT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_jobs_posted_by FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_jobs_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schemes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(190) NOT NULL,
  description TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_schemes_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_edits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  field_name VARCHAR(100) NOT NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  edited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_admin_edits_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_edits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_admin_edits_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_teams_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS team_members (
  team_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role_in_team VARCHAR(120) NOT NULL DEFAULT 'Member',
  assigned_by INT UNSIGNED NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (team_id, user_id),
  CONSTRAINT fk_team_members_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
  CONSTRAINT fk_team_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_team_members_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Create initial admin (change password immediately after first login).
-- Password below is for: Admin@12345
INSERT INTO users (name, email, password_hash, role)
SELECT 'System Admin', 'admin@village.local', '$2y$10$tB0MIZ6QX5YYvP5RqXjv6u0p6jT4CScu2SP6v6zIsfRSAxEPPGjM2', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@village.local');
