-- Tickets4You SRS v3.0 educational prototype. MySQL 8 / MariaDB 10.4+, utf8mb4.
CREATE DATABASE IF NOT EXISTS t4y_chatgpt_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE t4y_chatgpt_db;
SET time_zone = '+08:00';

CREATE TABLE IF NOT EXISTS staff_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE,
 permissions_json TEXT NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(160) NOT NULL,
 email VARCHAR(255) NOT NULL UNIQUE, phone VARCHAR(40) NOT NULL, password_hash VARCHAR(255) NOT NULL,
 staff_id VARCHAR(32) NULL UNIQUE, staff_category_id BIGINT UNSIGNED NULL,
 verified_at DATETIME NULL, status ENUM('active','inactive','anonymized') NOT NULL DEFAULT 'active',
 force_password_change TINYINT(1) NOT NULL DEFAULT 0,
 current_session_hash CHAR(64) NULL, last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (staff_category_id) REFERENCES staff_categories(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS user_roles (
 user_id BIGINT UNSIGNED NOT NULL, role ENUM('customer','organiser','staff','admin') NOT NULL,
 PRIMARY KEY(user_id,role), FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS auth_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 purpose ENUM('verify','email_change','reset') NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE,
 requested_email VARCHAR(255) NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(id), INDEX(user_id,purpose)
) ENGINE=InnoDB;
-- UAM Phase 1: protects every role login against repeated password guesses.
CREATE TABLE IF NOT EXISTS auth_login_attempts (
 subject_key CHAR(64) PRIMARY KEY,
 failures INT UNSIGNED NOT NULL DEFAULT 0,
 window_started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 blocked_until DATETIME NULL,
 INDEX (blocked_until)
) ENGINE=InnoDB;

-- UAM Phase 2: short-lived, single-use password-change email OTP.
CREATE TABLE IF NOT EXISTS account_password_otps (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 otp_hash CHAR(64) NOT NULL,
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;
-- Optional, consent-based analytics profile. Not required for booking or account access.
CREATE TABLE IF NOT EXISTS customer_research_profiles (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 age_band VARCHAR(16) NULL, gender VARCHAR(32) NULL, region VARCHAR(64) NULL,
 occupation VARCHAR(64) NULL, interests_json TEXT NULL,
 analytics_consent TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, actor_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL, entity VARCHAR(80) NOT NULL, entity_id BIGINT UNSIGNED NULL,
 details VARCHAR(500) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(actor_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 subject VARCHAR(190) NOT NULL, body TEXT NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0,
 email_status ENUM('queued','sent','failed','permanent') NOT NULL DEFAULT 'queued', attempts INT NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NULL, last_error VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), INDEX(email_status,next_attempt_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS venues (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(32) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL, level_no INT NOT NULL, capacity INT NOT NULL,
 parent_id BIGINT UNSIGNED NULL, facilities TEXT NULL,
 status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
 FOREIGN KEY(parent_id) REFERENCES venues(id), CHECK(capacity>0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS venue_layouts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, venue_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL, capacity INT NOT NULL, status ENUM('active','archived') NOT NULL DEFAULT 'active',
 FOREIGN KEY(venue_id) REFERENCES venues(id), UNIQUE(venue_id,name), CHECK(capacity>0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS venue_blocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, venue_id BIGINT UNSIGNED NOT NULL,
 start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, reason VARCHAR(255) NOT NULL,
 status ENUM('active','cancelled') NOT NULL DEFAULT 'active',
 FOREIGN KEY(venue_id) REFERENCES venues(id), INDEX(venue_id,start_at,end_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_applications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, applicant_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL, description TEXT NOT NULL, category_tag VARCHAR(80) NOT NULL,
 contact_phone VARCHAR(40) NOT NULL, expected_attendance INT NOT NULL,
 preferred_venue_id BIGINT UNSIGNED NOT NULL, preferred_start DATETIME NOT NULL, preferred_end DATETIME NOT NULL,
 staffing_plan VARCHAR(500) NOT NULL, remarks TEXT NULL,
 status ENUM('pending','under_review','revision','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(applicant_id) REFERENCES users(id), FOREIGN KEY(preferred_venue_id) REFERENCES venues(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, application_id BIGINT UNSIGNED NULL UNIQUE,
 organiser_id BIGINT UNSIGNED NOT NULL, venue_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL, description TEXT NOT NULL, category_tag VARCHAR(80) NOT NULL,
 status ENUM('draft','published','ongoing','completed','postponed','cancelled','archived') NOT NULL DEFAULT 'draft',
 sales_start DATETIME NULL, sales_end DATETIME NULL, published_at DATETIME NULL,
 visible_before_sales TINYINT(1) NOT NULL DEFAULT 1,
 max_per_order INT NOT NULL DEFAULT 4, hold_minutes INT NOT NULL DEFAULT 5,
 fee_per_ticket DECIMAL(12,2) NOT NULL DEFAULT 0.00,
 transfers_enabled TINYINT(1) NOT NULL DEFAULT 0,
 postponed_at DATETIME NULL, postpone_window_days INT NOT NULL DEFAULT 14,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(application_id) REFERENCES event_applications(id),
 FOREIGN KEY(organiser_id) REFERENCES users(id), FOREIGN KEY(venue_id) REFERENCES venues(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS event_organisers (
 event_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, primary_contact TINYINT(1) NOT NULL DEFAULT 0,
 PRIMARY KEY(event_id,user_id), FOREIGN KEY(event_id) REFERENCES events(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS event_translations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_id BIGINT UNSIGNED NOT NULL,
 locale VARCHAR(8) NOT NULL, title VARCHAR(190) NOT NULL, description TEXT NOT NULL,
 approved TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(event_id) REFERENCES events(id), UNIQUE(event_id,locale)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS event_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, event_id BIGINT UNSIGNED NOT NULL,
 venue_id BIGINT UNSIGNED NOT NULL, layout_id BIGINT UNSIGNED NULL,
 label VARCHAR(130) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL,
 capacity INT NOT NULL, setup_minutes INT NOT NULL DEFAULT 0, teardown_minutes INT NOT NULL DEFAULT 0,
 status ENUM('scheduled','postponed','cancelled','archived') NOT NULL DEFAULT 'scheduled',
 postponed_at DATETIME NULL,
 FOREIGN KEY(event_id) REFERENCES events(id), FOREIGN KEY(venue_id) REFERENCES venues(id),
 FOREIGN KEY(layout_id) REFERENCES venue_layouts(id), INDEX(venue_id,start_at,end_at), CHECK(capacity>=0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS session_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, session_id BIGINT UNSIGNED NOT NULL,
 old_start DATETIME NULL, new_start DATETIME NULL, reason VARCHAR(255) NOT NULL,
 changed_by BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(session_id) REFERENCES event_sessions(id), FOREIGN KEY(changed_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS ticket_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, session_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL, description VARCHAR(500) NULL,
 type ENUM('paid','free','complimentary') NOT NULL DEFAULT 'paid',
 price DECIMAL(12,2) NOT NULL DEFAULT 0.00, quota INT NOT NULL,
 eligibility VARCHAR(120) NULL, status ENUM('active','archived') NOT NULL DEFAULT 'active',
 FOREIGN KEY(session_id) REFERENCES event_sessions(id), UNIQUE(session_id,name), CHECK(quota>=0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS refund_policy_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL UNIQUE,
 created_by BIGINT UNSIGNED NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS refund_policy_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL,
 template_id BIGINT UNSIGNED NULL, event_id BIGINT UNSIGNED NULL, category_id BIGINT UNSIGNED NULL, version_no INT NOT NULL,
 fee_refundable TINYINT(1) NOT NULL DEFAULT 0, published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 created_by BIGINT UNSIGNED NOT NULL,
 FOREIGN KEY(template_id) REFERENCES refund_policy_templates(id), FOREIGN KEY(event_id) REFERENCES events(id), FOREIGN KEY(category_id) REFERENCES ticket_categories(id),
 FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS refund_tiers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, policy_id BIGINT UNSIGNED NOT NULL,
 minimum_seconds BIGINT UNSIGNED NOT NULL, percentage DECIMAL(5,2) NOT NULL,
 FOREIGN KEY(policy_id) REFERENCES refund_policy_versions(id), UNIQUE(policy_id,minimum_seconds)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reward_programs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(120) NOT NULL,
 points_per_myr DECIMAL(8,2) NOT NULL DEFAULT 1.00,
 redeem_myr_per_point DECIMAL(10,4) NOT NULL DEFAULT 0.0100,
 active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS promotions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code_hash CHAR(64) NOT NULL UNIQUE,
 label VARCHAR(100) NOT NULL, event_id BIGINT UNSIGNED NULL,
 type ENUM('fixed','percentage') NOT NULL, value DECIMAL(10,2) NOT NULL,
 max_uses INT NOT NULL DEFAULT 100, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 FOREIGN KEY(event_id) REFERENCES events(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS bundle_offers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, session_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL, price DECIMAL(12,2) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 FOREIGN KEY(session_id) REFERENCES event_sessions(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS bundle_components (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bundle_id BIGINT UNSIGNED NOT NULL,
 category_id BIGINT UNSIGNED NOT NULL, qty INT NOT NULL,
 FOREIGN KEY(bundle_id) REFERENCES bundle_offers(id), FOREIGN KEY(category_id) REFERENCES ticket_categories(id),
 UNIQUE(bundle_id,category_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS booking_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, booking_ref VARCHAR(32) NOT NULL UNIQUE,
 user_id BIGINT UNSIGNED NOT NULL, session_id BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','paid','cancelled','failed','expired') NOT NULL DEFAULT 'pending',
 subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 fee_total DECIMAL(12,2) NOT NULL DEFAULT 0, grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 discount_source VARCHAR(32) NULL, discount_ref BIGINT UNSIGNED NULL,
 hold_expires_at DATETIME NOT NULL, paid_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(session_id) REFERENCES event_sessions(id),
 INDEX(session_id,status,hold_expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS booking_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL,
 category_id BIGINT UNSIGNED NOT NULL, qty INT NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL, unit_discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 unit_fee DECIMAL(12,2) NOT NULL DEFAULT 0, policy_id BIGINT UNSIGNED NULL,
 FOREIGN KEY(order_id) REFERENCES booking_orders(id), FOREIGN KEY(category_id) REFERENCES ticket_categories(id),
 FOREIGN KEY(policy_id) REFERENCES refund_policy_versions(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS payment_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL,
 attempt_key VARCHAR(100) NOT NULL UNIQUE, method ENUM('fpx','card','ewallet','free') NOT NULL,
 status ENUM('success','failed') NOT NULL, amount DECIMAL(12,2) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES booking_orders(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS tickets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, booking_item_id BIGINT UNSIGNED NOT NULL,
 ticket_code VARCHAR(40) NOT NULL UNIQUE, token_nonce CHAR(32) NOT NULL,
 holder_email VARCHAR(255) NOT NULL,
 status ENUM('valid','refund_pending','refunded','void') NOT NULL DEFAULT 'valid',
 paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0, fee_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
 discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0, policy_id BIGINT UNSIGNED NULL,
 original_session_start DATETIME NULL,
 checked_in_at DATETIME NULL, issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(booking_item_id) REFERENCES booking_items(id), FOREIGN KEY(policy_id) REFERENCES refund_policy_versions(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS external_mail (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 recipient_email VARCHAR(255) NOT NULL, subject VARCHAR(190) NOT NULL, body TEXT NOT NULL,
 status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued', attempts INT NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_error VARCHAR(255) NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS ticket_transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id BIGINT UNSIGNED NOT NULL,
 recipient_email VARCHAR(255) NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL, accepted_at DATETIME NULL,
 FOREIGN KEY(ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS guest_ticket_access (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL,
 FOREIGN KEY(ticket_id) REFERENCES tickets(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS checkins (
 ticket_id BIGINT UNSIGNED PRIMARY KEY, staff_id BIGINT UNSIGNED NOT NULL,
 checked_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(ticket_id) REFERENCES tickets(id), FOREIGN KEY(staff_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS promo_redemptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, promotion_id BIGINT UNSIGNED NOT NULL,
 order_id BIGINT UNSIGNED NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL,
 FOREIGN KEY(promotion_id) REFERENCES promotions(id), FOREIGN KEY(order_id) REFERENCES booking_orders(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS reward_ledger (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 order_id BIGINT UNSIGNED NULL, points_delta INT NOT NULL,
 entry_type VARCHAR(30) NOT NULL, reference_key VARCHAR(100) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(order_id) REFERENCES booking_orders(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS support_cases (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 event_id BIGINT UNSIGNED NULL, ticket_id BIGINT UNSIGNED NULL,
 type ENUM('general','booking','ticket','payment','dispute','refund','staff') NOT NULL DEFAULT 'general',
 subject VARCHAR(160) NOT NULL, description TEXT NOT NULL,
 priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
 status ENUM('open','in_review','resolved','closed','archived') NOT NULL DEFAULT 'open',
 approval_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
 financial_status ENUM('none','pending','completed','needs_action') NOT NULL DEFAULT 'none',
 estimate DECIMAL(12,2) NULL, policy_id BIGINT UNSIGNED NULL,
 submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(event_id) REFERENCES events(id),
 FOREIGN KEY(ticket_id) REFERENCES tickets(id), FOREIGN KEY(policy_id) REFERENCES refund_policy_versions(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS case_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(case_id) REFERENCES support_cases(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS refund_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 ticket_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL,
 status ENUM('completed','failed') NOT NULL, idempotency_key VARCHAR(100) NOT NULL UNIQUE,
 processed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(case_id) REFERENCES support_cases(id), FOREIGN KEY(ticket_id) REFERENCES tickets(id),
 INDEX(case_id,status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_shifts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, staff_user_id BIGINT UNSIGNED NOT NULL,
 event_id BIGINT UNSIGNED NOT NULL, session_id BIGINT UNSIGNED NULL,
 start_at DATETIME NOT NULL, end_at DATETIME NOT NULL,
 status ENUM('scheduled','cancelled') NOT NULL DEFAULT 'scheduled',
 FOREIGN KEY(staff_user_id) REFERENCES users(id), FOREIGN KEY(event_id) REFERENCES events(id),
 FOREIGN KEY(session_id) REFERENCES event_sessions(id), INDEX(staff_user_id,start_at,end_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS user_preferences (
 user_id BIGINT UNSIGNED NOT NULL, category_tag VARCHAR(80) NOT NULL,
 PRIMARY KEY(user_id,category_tag), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS user_consents (
 user_id BIGINT UNSIGNED PRIMARY KEY,
 personalized TINYINT(1) NOT NULL DEFAULT 0, promo_email TINYINT(1) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS favorites (
 user_id BIGINT UNSIGNED NOT NULL, event_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,event_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(event_id) REFERENCES events(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS recommendation_feedback (
 user_id BIGINT UNSIGNED NOT NULL, event_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(user_id,event_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(event_id) REFERENCES events(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mfa_factors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 encrypted_secret TEXT NOT NULL, status ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
 enabled_at DATETIME NULL, last_accepted_step BIGINT NULL, FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS mfa_challenges (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 challenge_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL,
 attempts INT NOT NULL DEFAULT 0, verified_at DATETIME NULL,
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS mfa_recovery_codes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, factor_id BIGINT UNSIGNED NOT NULL,
 code_hash VARCHAR(255) NOT NULL, used_at DATETIME NULL,
 FOREIGN KEY(factor_id) REFERENCES mfa_factors(id)
) ENGINE=InnoDB;
