-- =============================================================================
-- 0001_create_account_domain.sql
-- 레거시 accounts 단일 테이블에서 "계정(인증/식별/권한)" 책임만 분리한다.
-- 근거: reference/logd-0.9.7-create.sql 의 CREATE TABLE accounts 및 후속 ALTER 문
--       (emailaddress, emailvalidation, uniqueid, lastip varchar(40), superuser2,
--        banoverride, beta, sentnotice).
-- 대상: SQLite 3 (PDO sqlite). MyISAM/INNODB 지정과 int(11) 폭 표기는 SQLite에
--       의미가 없으므로 제거하고, 무결성은 제약조건으로 대체한다.
-- =============================================================================

PRAGMA foreign_keys = ON;

-- -----------------------------------------------------------------------------
-- account : 로그인 자격증명과 계정 수명주기.
-- 레거시 대응 컬럼 — acctid, login, password, emailaddress, emailvalidation,
--                    laston, lasthit, loggedin, locked, sentnotice
-- -----------------------------------------------------------------------------
CREATE TABLE account (
    account_id           INTEGER PRIMARY KEY AUTOINCREMENT,
    login_name           TEXT    NOT NULL,
    password_hash        TEXT    NOT NULL,
    email_address        TEXT    NOT NULL DEFAULT '',
    email_validation_key TEXT    NOT NULL DEFAULT '',
    email_validated      INTEGER NOT NULL DEFAULT 0 CHECK (email_validated IN (0, 1)),
    is_locked            INTEGER NOT NULL DEFAULT 0 CHECK (is_locked IN (0, 1)),
    is_logged_in         INTEGER NOT NULL DEFAULT 0 CHECK (is_logged_in IN (0, 1)),
    expiration_notice_sent INTEGER NOT NULL DEFAULT 0 CHECK (expiration_notice_sent IN (0, 1)),
    created_at           TEXT    NOT NULL DEFAULT (datetime('now')),
    last_seen_at         TEXT,
    last_hit_at          TEXT,
    CONSTRAINT account_login_name_unique UNIQUE (login_name)
);

CREATE INDEX account_email_address_index ON account (email_address);
CREATE INDEX account_last_seen_at_index  ON account (last_seen_at);
CREATE INDEX account_is_locked_index     ON account (is_locked);

-- -----------------------------------------------------------------------------
-- account_privilege : 권한 등급.
-- 레거시 대응 컬럼 — superuser, superuser2, banoverride, beta
-- 레거시 superuser 등급 의미(reference/configuration.php):
--   0 표준 플레이, 1 무제한 플레이 일수, 2 크리처/도발 관리, 3 사용자 관리
-- -----------------------------------------------------------------------------
CREATE TABLE account_privilege (
    account_id            INTEGER PRIMARY KEY,
    superuser_level       INTEGER NOT NULL DEFAULT 0 CHECK (superuser_level BETWEEN 0 AND 3),
    superuser_flag_bitmap INTEGER NOT NULL DEFAULT 0,
    ban_override          INTEGER NOT NULL DEFAULT 0 CHECK (ban_override IN (0, 1)),
    beta_enabled          INTEGER NOT NULL DEFAULT 0 CHECK (beta_enabled IN (0, 1)),
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- account_device_fingerprint : 접속 단말 식별.
-- 레거시 대응 컬럼 — lastip (varchar(40) 로 확장된 최종형), uniqueid (쿠키 lgi)
-- -----------------------------------------------------------------------------
CREATE TABLE account_device_fingerprint (
    account_id     INTEGER PRIMARY KEY,
    last_ip_address TEXT   NOT NULL DEFAULT '',
    unique_cookie_id TEXT  NOT NULL DEFAULT '',
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE CASCADE
);

CREATE INDEX account_device_fingerprint_ip_index
    ON account_device_fingerprint (last_ip_address);
CREATE INDEX account_device_fingerprint_cookie_index
    ON account_device_fingerprint (unique_cookie_id);

-- -----------------------------------------------------------------------------
-- account_preference : 사용자 환경설정.
-- 레거시 대응 컬럼 — prefs (PHP serialize 문자열). JSON 으로 정규화하되
-- 언어 선택은 조회 빈도가 높아 별도 컬럼으로 승격한다 (reference/translator.php).
-- -----------------------------------------------------------------------------
CREATE TABLE account_preference (
    account_id       INTEGER PRIMARY KEY,
    locale_code      TEXT    NOT NULL DEFAULT 'en'
                     CHECK (locale_code IN ('en', 'ko', 'ja', 'zh', 'ru')),
    template_name    TEXT    NOT NULL DEFAULT 'yarbrough',
    preference_json  TEXT    NOT NULL DEFAULT '{}',
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- account_donation : 후원 상태.
-- 레거시 대응 컬럼 — donation, donationspent, donationconfig
-- -----------------------------------------------------------------------------
CREATE TABLE account_donation (
    account_id          INTEGER PRIMARY KEY,
    donation_point      INTEGER NOT NULL DEFAULT 0 CHECK (donation_point >= 0),
    donation_point_spent INTEGER NOT NULL DEFAULT 0 CHECK (donation_point_spent >= 0),
    donation_config_json TEXT   NOT NULL DEFAULT '{}',
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- account_referral : 추천인 관계.
-- 레거시 대응 컬럼 — referer, refererawarded
-- -----------------------------------------------------------------------------
CREATE TABLE account_referral (
    account_id           INTEGER PRIMARY KEY,
    referrer_account_id  INTEGER,
    referral_awarded     INTEGER NOT NULL DEFAULT 0 CHECK (referral_awarded IN (0, 1)),
    FOREIGN KEY (account_id)          REFERENCES account (account_id) ON DELETE CASCADE,
    FOREIGN KEY (referrer_account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX account_referral_referrer_index ON account_referral (referrer_account_id);
