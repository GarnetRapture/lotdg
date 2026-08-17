-- =============================================================================
-- 0004_create_community_domain.sql
-- 커뮤니티(뉴스/공지/댓글/우편/청원/투표) 도메인을 정의한다.
-- 선행: 0001_create_account_domain.sql, 0002_create_character_domain.sql
-- 소비: 뉴스·우편·댓글·청원 리포지토리.
-- 근거: reference/logd-0.9.7-create.sql 의 news, motd, commentary, mail,
--       petitions, pollresults 테이블 및 후속 ALTER(news.accountid,
--       motd.motdtype, commentary/mail 인덱스).
-- =============================================================================

PRAGMA foreign_keys = ON;

-- -----------------------------------------------------------------------------
-- daily_news : 일일 뉴스.
-- 레거시 대응 — news(newsid, newstext, newsdate, accountid)
-- 본문에는 레거시 색상코드(`^ `0 등)가 그대로 들어 있다.
-- -----------------------------------------------------------------------------
CREATE TABLE daily_news (
    news_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    news_text    TEXT    NOT NULL DEFAULT '',
    news_date    TEXT    NOT NULL,
    account_id   INTEGER,
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX daily_news_date_index       ON daily_news (news_date);
CREATE INDEX daily_news_account_id_index ON daily_news (account_id);

-- -----------------------------------------------------------------------------
-- message_of_the_day : 운영 공지/설문.
-- 레거시 대응 — motd(motditem, motdtitle, motdbody, motddate, motdtype)
-- motdtype: 0 일반 공지, 그 외 값은 설문(reference/motd.php 가 pollresults 와 연동).
-- -----------------------------------------------------------------------------
CREATE TABLE message_of_the_day (
    motd_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT    NOT NULL DEFAULT '',
    body        TEXT    NOT NULL DEFAULT '',
    motd_type   INTEGER NOT NULL DEFAULT 0 CHECK (motd_type >= 0),
    posted_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX message_of_the_day_posted_at_index ON message_of_the_day (posted_at);

-- -----------------------------------------------------------------------------
-- poll_result : 공지 설문 응답.
-- 레거시 대응 — pollresults(resultid, choice, account, motditem)
-- 레거시는 계정당 중복 응답을 코드로 막았으나 제약이 없었다. 여기서는 유일제약으로 승격한다.
-- -----------------------------------------------------------------------------
CREATE TABLE poll_result (
    poll_result_id INTEGER PRIMARY KEY AUTOINCREMENT,
    motd_id        INTEGER NOT NULL,
    account_id     INTEGER NOT NULL,
    choice_index   INTEGER NOT NULL CHECK (choice_index >= 0),
    FOREIGN KEY (motd_id)    REFERENCES message_of_the_day (motd_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES account (account_id)         ON DELETE CASCADE,
    CONSTRAINT poll_result_unique_vote UNIQUE (motd_id, account_id)
);

-- -----------------------------------------------------------------------------
-- commentary : 구역별 대화.
-- 레거시 대응 — commentary(commentid, section, author, comment, postdate)
-- section 은 페이지 식별자 문자열(village, inn, forest 등)이며,
-- comment 는 최대 200자였다(reference/common.php addcommentary()).
-- -----------------------------------------------------------------------------
CREATE TABLE commentary (
    commentary_id  INTEGER PRIMARY KEY AUTOINCREMENT,
    section_code   TEXT    NOT NULL,
    author_account_id INTEGER,
    comment_text   TEXT    NOT NULL DEFAULT '',
    posted_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (author_account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX commentary_section_index   ON commentary (section_code);
CREATE INDEX commentary_posted_at_index ON commentary (posted_at);

-- -----------------------------------------------------------------------------
-- mail_message : 캐릭터 간 우편.
-- 레거시 대응 — mail(messageid, msgfrom, msgto, subject, body, sent, seen)
-- msgfrom = 0 은 시스템 발신이었다(reference/common.php systemmail()).
-- -----------------------------------------------------------------------------
CREATE TABLE mail_message (
    mail_message_id  INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_account_id INTEGER,
    recipient_account_id INTEGER NOT NULL,
    subject          TEXT    NOT NULL DEFAULT '',
    body             TEXT    NOT NULL DEFAULT '',
    is_system_message INTEGER NOT NULL DEFAULT 0 CHECK (is_system_message IN (0, 1)),
    is_seen          INTEGER NOT NULL DEFAULT 0 CHECK (is_seen IN (0, 1)),
    sent_at          TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (sender_account_id)    REFERENCES account (account_id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_account_id) REFERENCES account (account_id) ON DELETE CASCADE
);

CREATE INDEX mail_message_recipient_index ON mail_message (recipient_account_id, is_seen);
CREATE INDEX mail_message_sent_at_index   ON mail_message (sent_at);

-- -----------------------------------------------------------------------------
-- petition : 운영 문의/이의제기.
-- 레거시 대응 — petitions(petitionid, author, date, status, body, pageinfo)
-- status: 0 미확인, 1 확인, 2 종료 (reference/common.php page_footer() 의 집계 문구)
-- -----------------------------------------------------------------------------
CREATE TABLE petition (
    petition_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    author_account_id INTEGER,
    status_code     INTEGER NOT NULL DEFAULT 0 CHECK (status_code IN (0, 1, 2)),
    body            TEXT    NOT NULL DEFAULT '',
    page_info_json  TEXT    NOT NULL DEFAULT '{}',
    submitted_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (author_account_id) REFERENCES account (account_id) ON DELETE SET NULL
);

CREATE INDEX petition_status_index ON petition (status_code);
