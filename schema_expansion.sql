-- ArenaX feature expansion schema (run after schema.sql).
-- MySQL 8 / MariaDB compatible. Back up the database before applying.
USE arenax;

CREATE TABLE IF NOT EXISTS player_game_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 game VARCHAR(60) NOT NULL, game_uid VARCHAR(120) NOT NULL, in_game_name VARCHAR(100) NOT NULL,
 region VARCHAR(80), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_game_uid(game,game_uid), UNIQUE KEY uniq_user_game(user_id,game),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teams (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, owner_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL, game VARCHAR(60) NOT NULL, invite_code CHAR(12) NOT NULL UNIQUE,
 description VARCHAR(500), status ENUM('active','disbanded') NOT NULL DEFAULT 'active',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(owner_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS team_members (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, team_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL, member_role ENUM('captain','member') NOT NULL DEFAULT 'member',
 status ENUM('invited','active','left','removed') NOT NULL DEFAULT 'invited', joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_team_member(team_id,user_id), INDEX idx_member_user(user_id,status),
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tournament_team_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tournament_id BIGINT UNSIGNED NOT NULL,
 team_id BIGINT UNSIGNED NOT NULL, captain_id BIGINT UNSIGNED NOT NULL,
 status ENUM('registered','checked_in','disqualified','completed','withdrawn') NOT NULL DEFAULT 'registered',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_tournament_team(tournament_id,team_id),
 FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
 FOREIGN KEY(team_id) REFERENCES teams(id), FOREIGN KEY(captain_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tournament_rounds (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tournament_id BIGINT UNSIGNED NOT NULL,
 round_number SMALLINT UNSIGNED NOT NULL, title VARCHAR(100) NOT NULL,
 status ENUM('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
 starts_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_tournament_round(tournament_id,round_number),
 FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS matches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tournament_id BIGINT UNSIGNED NOT NULL,
 round_id BIGINT UNSIGNED NULL, match_code VARCHAR(60) NOT NULL,
 game VARCHAR(60) NOT NULL, mode VARCHAR(40) NOT NULL,
 status ENUM('scheduled','room_ready','live','awaiting_result','under_review','completed','cancelled') NOT NULL DEFAULT 'scheduled',
 scheduled_at DATETIME NULL, room_id VARCHAR(100) NULL, room_password VARCHAR(100) NULL,
 result_deadline DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_match_code(tournament_id,match_code), INDEX idx_match_status(status,scheduled_at),
 FOREIGN KEY(tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
 FOREIGN KEY(round_id) REFERENCES tournament_rounds(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS match_participants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, match_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL, team_id BIGINT UNSIGNED NULL,
 seed_number SMALLINT UNSIGNED NULL, score INT NOT NULL DEFAULT 0,
 placement SMALLINT UNSIGNED NULL, kills SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 result_status ENUM('pending','submitted','approved','disputed','rejected') NOT NULL DEFAULT 'pending',
 UNIQUE KEY uniq_match_user(match_id,user_id), UNIQUE KEY uniq_match_team(match_id,team_id),
 FOREIGN KEY(match_id) REFERENCES matches(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(team_id) REFERENCES teams(id) ON DELETE SET NULL,
 CHECK (user_id IS NOT NULL OR team_id IS NOT NULL)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS result_submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, match_id BIGINT UNSIGNED NOT NULL,
 participant_id BIGINT UNSIGNED NOT NULL, submitted_by BIGINT UNSIGNED NOT NULL,
 placement SMALLINT UNSIGNED NULL, kills SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 score INT NOT NULL DEFAULT 0, proof_path VARCHAR(255), status ENUM('pending','approved','rejected','disputed') NOT NULL DEFAULT 'pending',
 review_note VARCHAR(500), reviewed_by BIGINT UNSIGNED NULL, reviewed_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(match_id) REFERENCES matches(id) ON DELETE CASCADE,
 FOREIGN KEY(participant_id) REFERENCES match_participants(id) ON DELETE CASCADE,
 FOREIGN KEY(submitted_by) REFERENCES users(id), FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matchmaking_queue (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 game VARCHAR(60) NOT NULL, mode VARCHAR(40) NOT NULL,
 skill_band VARCHAR(40), status ENUM('queued','matched','cancelled','expired') NOT NULL DEFAULT 'queued',
 queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, matched_at DATETIME NULL,
 INDEX idx_queue_match(game,mode,status,queued_at),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS player_stats (
 user_id BIGINT UNSIGNED PRIMARY KEY, matches_played INT UNSIGNED NOT NULL DEFAULT 0,
 wins INT UNSIGNED NOT NULL DEFAULT 0, kills BIGINT UNSIGNED NOT NULL DEFAULT 0,
 points BIGINT NOT NULL DEFAULT 0, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS player_follows (
 follower_id BIGINT UNSIGNED NOT NULL, followed_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(follower_id,followed_id),
 CHECK(follower_id <> followed_id), FOREIGN KEY(follower_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS push_subscriptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 endpoint TEXT NOT NULL, endpoint_hash CHAR(64) NOT NULL UNIQUE,
 p256dh VARCHAR(255) NOT NULL, auth_token VARCHAR(255) NOT NULL,
 user_agent VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 last_seen_at DATETIME NULL, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notification_preferences (
 user_id BIGINT UNSIGNED PRIMARY KEY, tournament_updates TINYINT(1) NOT NULL DEFAULT 1,
 wallet_updates TINYINT(1) NOT NULL DEFAULT 1, team_updates TINYINT(1) NOT NULL DEFAULT 1,
 community_updates TINYINT(1) NOT NULL DEFAULT 1, marketing TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notification_reads (
 notification_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(notification_id,user_id),
 FOREIGN KEY(notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS referral_codes (
 user_id BIGINT UNSIGNED PRIMARY KEY, code VARCHAR(24) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS referral_rewards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, referrer_id BIGINT UNSIGNED NOT NULL,
 referred_user_id BIGINT UNSIGNED NOT NULL, reward_coins INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_referred(referred_user_id),
 FOREIGN KEY(referrer_id) REFERENCES users(id), FOREIGN KEY(referred_user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS reward_claims (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 reward_type VARCHAR(60) NOT NULL, reward_reference VARCHAR(100) NOT NULL,
 coins BIGINT UNSIGNED NOT NULL DEFAULT 0, status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
 reviewed_by BIGINT UNSIGNED NULL, reviewed_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_reward_claim(user_id,reward_type,reward_reference),
 FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS platform_settings (
 setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL,
 is_secret TINYINT(1) NOT NULL DEFAULT 0, updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS feature_flags (
 flag_key VARCHAR(100) PRIMARY KEY, enabled TINYINT(1) NOT NULL DEFAULT 0,
 description VARCHAR(255), updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS admin_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 session_hash CHAR(64) NOT NULL UNIQUE, ip_address VARCHAR(45), user_agent VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, last_seen_at DATETIME NULL, revoked_at DATETIME NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS support_ticket_replies (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticket_id BIGINT UNSIGNED NOT NULL,
 author_id BIGINT UNSIGNED NOT NULL, message TEXT NOT NULL, is_internal TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
 FOREIGN KEY(author_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS prize_payouts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tournament_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL, team_id BIGINT UNSIGNED NULL, amount_coins BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
 payout_reference VARCHAR(120), approved_by BIGINT UNSIGNED NULL, paid_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(tournament_id) REFERENCES tournaments(id), FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(team_id) REFERENCES teams(id), FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB;


-- Admin-editable platform configuration (safe operational values only)
CREATE TABLE IF NOT EXISTS app_settings (
 setting_key VARCHAR(80) PRIMARY KEY,
 setting_value TEXT NOT NULL,
 updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO app_settings(setting_key,setting_value) VALUES
 ('app_name','ArenaX'),('support_email',''),('upi_id',''),('coins_per_rupee','10'),
 ('registration_enabled','1'),('maintenance_mode','0'),('home_notice',''),('referral_coins','0'),('min_topup_rupees','50');
