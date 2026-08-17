-- =============================================================================
-- 0005_create_operation_domain.sql
-- 운영 도메인(게임 설정/차단/금칙어/서버 목록/유입 경로/로그)을 정의한다.
-- 선행: 0001_create_account_domain.sql (로그 테이블이 account 를 참조한다)
-- 소비: 커널 설정 로더, 차단 검사 미들웨어, 관리 화면.
-- 근거: reference/logd-0.9.7-create.sql 의 settings, bans, nastywords, logdnet,
--       referers, faillog, debuglog 테이블 및 ALTER(settings.value varchar(255),
--       referers.site).
-- =============================================================================

PRAGMA foreign_keys = ON;

-- -----------------------------------------------------------------------------
-- game_setting : 키-값 게임 설정.
-- 레거시 대응 — settings(setting, value)
-- 키 목록은 reference/configuration.php 의 $setup 배열이 정의한다
-- (daysperday, turns, pvp, bountymin, LOGINTIMEOUT, defaultlanguage 등).
-- -----------------------------------------------------------------------------
CREATE TABLE game_setting (
    setting_key   TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL DEFAULT '',
    updated_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

-- -----------------------------------------------------------------------------
-- access_ban : 접속 차단.
-- 레거시 대응 — bans(ipfilter, uniqueid, banexpire, banreason)
-- 레거시는 ipfilter 를 접두사 비교로 사용했다
-- (substring(ip,1,length(ipfilter)) = ipfilter, reference/common.php checkban()).
-- ban_expire_date 가 NULL 이면 영구 차단이다.
-- -----------------------------------------------------------------------------
CREATE TABLE access_ban (
    access_ban_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_prefix        TEXT NOT NULL DEFAULT '',
    unique_cookie_id TEXT NOT NULL DEFAULT '',
    ban_expire_date  TEXT,
    ban_reason       TEXT NOT NULL DEFAULT '',
    created_at       TEXT NOT NULL DEFAULT (datetime('now')),
    CONSTRAINT access_ban_target_present
        CHECK (ip_prefix <> '' OR unique_cookie_id <> '')
);

CREATE INDEX access_ban_ip_prefix_index ON access_ban (ip_prefix);
CREATE INDEX access_ban_cookie_index    ON access_ban (unique_cookie_id);
CREATE INDEX access_ban_expire_index    ON access_ban (ban_expire_date);

-- -----------------------------------------------------------------------------
-- nasty_word : 금칙어.
-- 레거시 대응 — nastywords(words) 단일 행 공백구분 문자열.
-- 한 행 = 한 패턴으로 정규화한다. 패턴의 '*' 는 임의 영숫자열을 뜻하며,
-- reference/common.php soap() 이 leetspeak 치환(a→[a4@] 등) 후 정규식으로 변환한다.
-- -----------------------------------------------------------------------------
CREATE TABLE nasty_word (
    nasty_word_id INTEGER PRIMARY KEY AUTOINCREMENT,
    word_pattern  TEXT NOT NULL,
    CONSTRAINT nasty_word_pattern_unique UNIQUE (word_pattern)
);

-- -----------------------------------------------------------------------------
-- logdnet_server : LoGDnet 서버 목록.
-- 레거시 대응 — logdnet(serverid, address, description, priority, lastupdate)
-- -----------------------------------------------------------------------------
CREATE TABLE logdnet_server (
    logdnet_server_id INTEGER PRIMARY KEY AUTOINCREMENT,
    address           TEXT    NOT NULL,
    description       TEXT    NOT NULL DEFAULT '',
    priority          REAL    NOT NULL DEFAULT 100,
    last_updated_at   TEXT,
    CONSTRAINT logdnet_server_address_unique UNIQUE (address)
);

CREATE INDEX logdnet_server_priority_index ON logdnet_server (priority);

-- -----------------------------------------------------------------------------
-- referer_hit : 외부 유입 경로 집계.
-- 레거시 대응 — referers(refererid, uri, count, last, site)
-- -----------------------------------------------------------------------------
CREATE TABLE referer_hit (
    referer_hit_id INTEGER PRIMARY KEY AUTOINCREMENT,
    referer_uri    TEXT    NOT NULL,
    site_host      TEXT    NOT NULL DEFAULT '',
    hit_count      INTEGER NOT NULL DEFAULT 0 CHECK (hit_count >= 0),
    last_hit_at    TEXT,
    CONSTRAINT referer_hit_uri_unique UNIQUE (referer_uri)
);

CREATE INDEX referer_hit_site_index ON referer_hit (site_host);

-- -----------------------------------------------------------------------------
-- login_failure_log : 로그인 실패 기록.
-- 레거시 대응 — faillog(eventid, date, post, ip, acctid, id)
-- -----------------------------------------------------------------------------
CREATE TABLE login_failure_log (
    login_failure_log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    occurred_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    submitted_login  TEXT    NOT NULL DEFAULT '',
    ip_address       TEXT    NOT NULL DEFAULT '',
    account_id       INTEGER,
    unique_cookie_id TEXT    NOT NULL DEFAULT '',
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX login_failure_log_occurred_at_index ON login_failure_log (occurred_at);
CREATE INDEX login_failure_log_account_index     ON login_failure_log (account_id);
CREATE INDEX login_failure_log_ip_index          ON login_failure_log (ip_address);

-- -----------------------------------------------------------------------------
-- debug_log : 운영 디버그 기록.
-- 레거시 대응 — debuglog(id, date, actor, target, message)
-- -----------------------------------------------------------------------------
CREATE TABLE debug_log (
    debug_log_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    occurred_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    actor_account_id INTEGER,
    target_account_id INTEGER,
    message          TEXT    NOT NULL DEFAULT '',
    FOREIGN KEY (actor_account_id)  REFERENCES account (account_id) ON DELETE SET NULL,
    FOREIGN KEY (target_account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX debug_log_occurred_at_index ON debug_log (occurred_at);
CREATE INDEX debug_log_actor_index       ON debug_log (actor_account_id);
CREATE INDEX debug_log_target_index      ON debug_log (target_account_id);

-- -----------------------------------------------------------------------------
-- schema_migration : 마이그레이션 적용 이력.
-- 레거시에는 없던 테이블이다. 레거시 SQL 은 CREATE 와 ALTER 가 한 파일에
-- 누적된 형태여서 재적용 판별이 불가능했으므로, 적용 이력을 명시적으로 남긴다.
-- -----------------------------------------------------------------------------
CREATE TABLE schema_migration (
    migration_name TEXT PRIMARY KEY,
    applied_at     TEXT NOT NULL DEFAULT (datetime('now'))
);
