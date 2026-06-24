-- ============================================================
--  Event Registration System — Database Schema
--  SECJ3343 Software Quality Assurance | Project 1
-- ============================================================

CREATE DATABASE IF NOT EXISTS ers_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ers_db;

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255)  NOT NULL,
    age             INT           NOT NULL,
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    status          ENUM('active','locked','deactivated') NOT NULL DEFAULT 'active',
    failed_attempts INT           NOT NULL DEFAULT 0,
    locked_until    DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- EVENTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    name                  VARCHAR(150) NOT NULL UNIQUE,
    description           TEXT         NOT NULL,
    venue                 VARCHAR(150) NOT NULL,
    capacity              INT          NOT NULL CHECK (capacity >= 1),
    seats_remaining       INT          NOT NULL,
    event_date            DATETIME     NOT NULL,
    registration_deadline DATETIME     NOT NULL,
    status                ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- REGISTRATIONS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS registrations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT      NOT NULL,
    event_id      INT      NOT NULL,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_event (user_id, event_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- SESSIONS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT          NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at    DATETIME     NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- Seed: default admin account  (password: Admin@1234)
-- -------------------------------------------------------
INSERT IGNORE INTO users (full_name, email, password_hash, age, role, status)
VALUES (
    'System Admin',
    'admin@ers.com',
    '$2y$12$KIXdhA0OAiCEwrVFkCbpH.JpZ0e3l1TXJhf3OcxFuHixEhIp5JiZG',
    30,
    'admin',
    'active'
);

-- -------------------------------------------------------
-- Seed: sample events
-- -------------------------------------------------------
INSERT IGNORE INTO events (name, description, venue, capacity, seats_remaining, event_date, registration_deadline, status)
VALUES
(
    'AI & Machine Learning Summit 2026',
    'A full-day summit exploring the latest breakthroughs in artificial intelligence and machine learning, featuring industry speakers and hands-on workshops.',
    'Main Auditorium, UTM KL',
    100, 100,
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    DATE_ADD(NOW(), INTERVAL 25 DAY),
    'Open'
),
(
    'Web Development Bootcamp',
    'An intensive two-day bootcamp covering modern web development with HTML, CSS, PHP, and MySQL. Suitable for beginners and intermediates.',
    'Lab 3, Block A, UTM KL',
    40, 40,
    DATE_ADD(NOW(), INTERVAL 15 DAY),
    DATE_ADD(NOW(), INTERVAL 10 DAY),
    'Open'
),
(
    'Cybersecurity Awareness Workshop',
    'Interactive workshop on cybersecurity best practices, ethical hacking basics, and data protection strategies for students and professionals.',
    'Seminar Room 2, UTM KL',
    60, 60,
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    DATE_ADD(NOW(), INTERVAL 5 DAY),
    'Open'
);
