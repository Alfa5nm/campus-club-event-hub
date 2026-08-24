CREATE DATABASE IF NOT EXISTS campus_club_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campus_club_hub;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS password_reset_token, notification, announcement, feedback, certificate, attendance, event_registration, events, club_gallery, student_guidance, student_interest, club_membership, clubs, administrators, students, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NULL,
  role ENUM('Student','Admin') NOT NULL DEFAULT 'Student',
  profile_picture VARCHAR(255) NULL,
  status ENUM('Active','Suspended','Deactivated') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  student_number VARCHAR(40) NOT NULL UNIQUE,
  department VARCHAR(100) NOT NULL,
  academic_year VARCHAR(30) NULL,
  CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE administrators (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  admin_role VARCHAR(80) NOT NULL DEFAULT 'Administrator',
  CONSTRAINT fk_admin_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student_interest (
  student_user_id BIGINT UNSIGNED NOT NULL,
  interest VARCHAR(80) NOT NULL,
  PRIMARY KEY (student_user_id, interest),
  CONSTRAINT fk_interest_student FOREIGN KEY (student_user_id) REFERENCES students(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student_guidance (
  senior_student_user_id BIGINT UNSIGNED NOT NULL,
  junior_student_user_id BIGINT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (senior_student_user_id, junior_student_user_id),
  CONSTRAINT fk_guidance_senior FOREIGN KEY (senior_student_user_id) REFERENCES students(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_guidance_junior FOREIGN KEY (junior_student_user_id) REFERENCES students(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_different_students CHECK (senior_student_user_id <> junior_student_user_id)
) ENGINE=InnoDB;

CREATE TABLE clubs (
  club_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_name VARCHAR(140) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  category VARCHAR(80) NOT NULL,
  logo VARCHAR(255) NULL,
  contact_information VARCHAR(190) NULL,
  status ENUM('Pending','Active','Suspended') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_club_category_status (category, status)
) ENGINE=InnoDB;

CREATE TABLE club_membership (
  membership_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_user_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NOT NULL,
  join_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  member_role ENUM('Member','Executive','President','Vice President','Secretary','Treasurer') NOT NULL DEFAULT 'Member',
  approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  membership_status ENUM('Active','Removed','Resigned') NOT NULL DEFAULT 'Active',
  UNIQUE KEY uq_student_club (student_user_id, club_id),
  CONSTRAINT fk_membership_student FOREIGN KEY (student_user_id) REFERENCES students(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_membership_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
  INDEX idx_membership_club_state (club_id, approval_status, membership_status)
) ENGINE=InnoDB;

CREATE TABLE club_gallery (
  photo_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  photo_path VARCHAR(255) NOT NULL,
  caption VARCHAR(255) NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE events (
  event_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id BIGINT UNSIGNED NOT NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  event_category VARCHAR(80) NOT NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NULL,
  venue VARCHAR(180) NOT NULL,
  maximum_participants INT UNSIGNED NOT NULL,
  registration_deadline DATE NULL,
  poster VARCHAR(255) NULL,
  status ENUM('Draft','Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
  CONSTRAINT fk_event_creator FOREIGN KEY (created_by_user_id) REFERENCES users(user_id),
  CONSTRAINT chk_event_capacity CHECK (maximum_participants > 0),
  CONSTRAINT chk_event_deadline CHECK (registration_deadline IS NULL OR registration_deadline <= event_date),
  INDEX idx_event_discovery (status, event_date, event_category)
) ENGINE=InnoDB;

CREATE TABLE event_registration (
  registration_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_user_id BIGINT UNSIGNED NOT NULL,
  event_id BIGINT UNSIGNED NOT NULL,
  registration_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  registration_status ENUM('Registered','Cancelled','Attended','Absent') NOT NULL DEFAULT 'Registered',
  qr_token CHAR(64) NULL UNIQUE,
  cancellation_reason VARCHAR(255) NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_event (student_user_id, event_id),
  CONSTRAINT fk_registration_student FOREIGN KEY (student_user_id) REFERENCES students(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_registration_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
  INDEX idx_registration_event_status (event_id, registration_status)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  attendance_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id BIGINT UNSIGNED NOT NULL UNIQUE,
  marked_by_membership_id BIGINT UNSIGNED NULL,
  attendance_status ENUM('Present','Absent') NOT NULL,
  attendance_method ENUM('QR','Manual') NOT NULL,
  check_in_time DATETIME NULL,
  marked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_registration FOREIGN KEY (registration_id) REFERENCES event_registration(registration_id) ON DELETE CASCADE,
  CONSTRAINT fk_attendance_marker FOREIGN KEY (marked_by_membership_id) REFERENCES club_membership(membership_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE certificate (
  certificate_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_id BIGINT UNSIGNED NOT NULL UNIQUE,
  certificate_number VARCHAR(80) NOT NULL UNIQUE,
  issue_date DATE NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  verification_code VARCHAR(100) NOT NULL UNIQUE,
  status ENUM('Active','Revoked') NOT NULL DEFAULT 'Active',
  CONSTRAINT fk_certificate_attendance FOREIGN KEY (attendance_id) REFERENCES attendance(attendance_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE feedback (
  feedback_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id BIGINT UNSIGNED NOT NULL UNIQUE,
  rating TINYINT UNSIGNED NOT NULL,
  review_text TEXT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Visible','Hidden','Reported') NOT NULL DEFAULT 'Visible',
  CONSTRAINT fk_feedback_registration FOREIGN KEY (registration_id) REFERENCES event_registration(registration_id) ON DELETE CASCADE,
  CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE announcement (
  announcement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  publisher_user_id BIGINT UNSIGNED NOT NULL,
  club_id BIGINT UNSIGNED NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  announcement_type ENUM('Club Notice','Event Update','Event Cancellation','Registration Extension','Meeting Notice','System Notice') NOT NULL,
  published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expiry_date DATE NULL,
  status ENUM('Draft','Active','Expired','Removed') NOT NULL DEFAULT 'Active',
  notified_at DATETIME NULL,
  CONSTRAINT fk_announcement_publisher FOREIGN KEY (publisher_user_id) REFERENCES users(user_id),
  CONSTRAINT fk_announcement_club FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE,
  INDEX idx_announcement_active (status, expiry_date)
) ENGINE=InnoDB;

CREATE TABLE notification (
  notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id BIGINT UNSIGNED NOT NULL,
  notification_type VARCHAR(80) NOT NULL,
  message VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT fk_notification_user FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_notification_inbox (recipient_user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE password_reset_token (
  reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
