-- GABAY: complete database, fresh start.
-- WARNING: this DELETES the existing `gabay` database (all accounts, bookings, posts) and rebuilds it from scratch.
-- Import it in phpMyAdmin (Import tab) or run: mysql -u root < gabay.sql
DROP DATABASE IF EXISTS gabay;

-- GABAY schema. Import in phpMyAdmin (Import tab) or: mysql -u root < database/schema.sql
CREATE DATABASE IF NOT EXISTS gabay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gabay;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','counselor','admin') NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  alias VARCHAR(40) NULL UNIQUE,            -- students only
  title VARCHAR(80) NULL,                   -- counselors only: shown to students when booking
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,  -- set when an admin creates or resets the account
  is_active TINYINT(1) NOT NULL DEFAULT 1,  -- 0 = deactivated: cannot log in, but history is kept
  consented_at DATETIME NULL,               -- students: when they agreed that staff can read the Peer Circle
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  counselor_id INT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_taken TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_counselor_start (counselor_id, starts_at),
  KEY idx_open (is_taken, starts_at),
  FOREIGN KEY (counselor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  slot_id INT UNSIGNED NOT NULL,
  -- Double-booking guard: holds slot_id while the booking is active, NULL once
  -- cancelled or declined. The UNIQUE key means MySQL itself refuses a second active booking.
  active_slot_id INT UNSIGNED NULL UNIQUE,
  mode ENUM('in_person','chat','video') NOT NULL,
  note VARCHAR(500) NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  ref_code VARCHAR(10) NOT NULL UNIQUE,
  status ENUM('pending','confirmed','declined','done','cancelled') NOT NULL DEFAULT 'pending',
  counselor_notes TEXT NULL,                -- not encrypted (known gap)
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (slot_id) REFERENCES slots(id)
) ENGINE=InnoDB;

CREATE TABLE mood_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  entry_date DATE NOT NULL,
  score TINYINT UNSIGNED NOT NULL,          -- 1 (very low) to 5 (great)
  UNIQUE KEY uq_student_day (student_id, entry_date),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,              -- NULL = top-level post, otherwise a reply
  body VARCHAR(1000) NOT NULL,
  is_flagged TINYINT(1) NOT NULL DEFAULT 0,
  flag_source ENUM('crisis','report') NULL,
  review_state ENUM('open','reviewed') NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_board (parent_id, is_hidden, created_at),
  KEY idx_queue (is_flagged, review_state, is_hidden),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_reports (
  post_id INT UNSIGNED NOT NULL,
  reporter_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, reporter_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Starter admin (OSDS). Password: ChangeMe123!  Change it right after the first login.
INSERT INTO users (role, email, password_hash, full_name)
VALUES ('admin', 'admin@gabay.local', '$2y$10$8vmsqB91LguLubk20ERW6uOaPPzbM6ifO5bilj/OY7M.5.ZFNnhra', 'OSDS Admin');

-- Starter counselor accounts (demo). Password for all three: Counselor123!  Change them after the first login.
-- INSERT IGNORE means this block is safe to run again on a database that already has them.
INSERT IGNORE INTO users (role, email, password_hash, full_name) VALUES
('counselor', 'maria.santos@gabay.local', '$2y$10$PZXIeukPqNwKkON3qixSP.SM9ZKExUD3CCbt6aV3.cE9Fn1.9ZJXO', 'Maria Santos'),
('counselor', 'jose.reyes@gabay.local',   '$2y$10$PZXIeukPqNwKkON3qixSP.SM9ZKExUD3CCbt6aV3.cE9Fn1.9ZJXO', 'Jose Reyes'),
('counselor', 'ana.cruz@gabay.local',     '$2y$10$PZXIeukPqNwKkON3qixSP.SM9ZKExUD3CCbt6aV3.cE9Fn1.9ZJXO', 'Ana Cruz');
