-- =============================================================================
-- 0001_create_schema.sql
-- 레거시 logd 0.9.7 의 전체 스키마를 SQLite 3 정규형으로 재정의한 완전한 스키마.
-- 근거: reference/logd-0.9.7-create.sql 의 CREATE TABLE 과 후속 ALTER 를 모두
--       반영한 최종형이며, 값 범위는 reference 의 소비 코드에서 확인한 것이다.
-- 대상: SQLite 3 (PDO sqlite). MyISAM/INNODB 지정과 int(11) 폭 표기는 SQLite 에
--       의미가 없으므로 제거하고, 무결성은 제약조건으로 대체한다.
-- =============================================================================

PRAGMA foreign_keys = ON;

-- =============================================================================
-- 계정 도메인
-- 레거시 accounts 단일 테이블에서 인증/식별/권한 책임만 분리한다.
-- =============================================================================

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

-- =============================================================================
-- 캐릭터 도메인
-- 레거시 accounts 단일 테이블에서 스탯/전투/자산/일일상태 책임을 분리한다.
-- 근거: reference/common.php charstats()/$titles/$races, reference/train.php.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- game_character : 캐릭터 본체.
-- 레거시 대응 컬럼 — name, sex, race, level, title, ctitle, location, restorepage
-- 레거시 성별 값: 0 남성, 1 여성 (accounts.sex tinyint)
-- 레거시 종족 값: reference/common.php $races — 0 Unknown, 1 Troll, 2 Elf,
--                 3 Human, 4 Dwarf, 50 Hoversheep
-- 레거시 location 값: reference/logd-0.9.7-create.sql creatures.location —
--                     0 숲(forest), 1 묘지(graveyard)
-- -----------------------------------------------------------------------------
CREATE TABLE game_character (
    character_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    account_id       INTEGER NOT NULL,
    display_name     TEXT    NOT NULL DEFAULT '',
    sex_code         INTEGER NOT NULL DEFAULT 0 CHECK (sex_code IN (0, 1)),
    race_code        INTEGER NOT NULL DEFAULT 0 CHECK (race_code IN (0, 1, 2, 3, 4, 50)),
    level            INTEGER NOT NULL DEFAULT 1 CHECK (level >= 1),
    rank_title       TEXT    NOT NULL DEFAULT '',
    custom_title     TEXT    NOT NULL DEFAULT '',
    location_code    INTEGER NOT NULL DEFAULT 0 CHECK (location_code IN (0, 1)),
    restore_page_uri TEXT    NOT NULL DEFAULT '',
    FOREIGN KEY (account_id) REFERENCES account (account_id) ON DELETE CASCADE,
    CONSTRAINT game_character_account_unique UNIQUE (account_id)
);

CREATE INDEX game_character_display_name_index ON game_character (display_name);
CREATE INDEX game_character_level_index        ON game_character (level);

-- -----------------------------------------------------------------------------
-- character_vital : 생명/사망 상태.
-- 레거시 대응 컬럼 — hitpoints, maxhitpoints, alive, spirits, soulpoints,
--                    gravefights, deathpower, hauntpoints, hauntedby, slainby, killedin
-- 레거시 spirits 값: reference/common.php charstats() — -6 죽음, -2 매우 낮음,
--                    -1 낮음, 0 보통, 1 높음, 2 매우 높음
-- -----------------------------------------------------------------------------
CREATE TABLE character_vital (
    character_id      INTEGER PRIMARY KEY,
    hit_point         INTEGER NOT NULL DEFAULT 10,
    max_hit_point     INTEGER NOT NULL DEFAULT 10 CHECK (max_hit_point >= 0),
    is_alive          INTEGER NOT NULL DEFAULT 1 CHECK (is_alive IN (0, 1)),
    spirit_level      INTEGER NOT NULL DEFAULT 0 CHECK (spirit_level IN (-6, -2, -1, 0, 1, 2)),
    soul_point        INTEGER NOT NULL DEFAULT 0 CHECK (soul_point >= 0),
    grave_fight       INTEGER NOT NULL DEFAULT 0 CHECK (grave_fight >= 0),
    death_power       INTEGER NOT NULL DEFAULT 0 CHECK (death_power >= 0),
    haunt_point       INTEGER NOT NULL DEFAULT 0 CHECK (haunt_point >= 0),
    haunted_by_name   TEXT    NOT NULL DEFAULT '',
    slain_by_name     TEXT    NOT NULL DEFAULT '',
    killed_in_area    TEXT    NOT NULL DEFAULT '',
    resurrection_count INTEGER NOT NULL DEFAULT 0 CHECK (resurrection_count >= 0),
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

CREATE INDEX character_vital_is_alive_index ON character_vital (is_alive);

-- -----------------------------------------------------------------------------
-- character_combat_stat : 전투 능력치.
-- 레거시 대응 컬럼 — attack, defence, bufflist, buffbackup, badguy
-- -----------------------------------------------------------------------------
CREATE TABLE character_combat_stat (
    character_id     INTEGER PRIMARY KEY,
    attack_point     INTEGER NOT NULL DEFAULT 1 CHECK (attack_point >= 0),
    defence_point    INTEGER NOT NULL DEFAULT 1 CHECK (defence_point >= 0),
    buff_list_json   TEXT    NOT NULL DEFAULT '{}',
    buff_backup_json TEXT    NOT NULL DEFAULT '{}',
    current_enemy_json TEXT  NOT NULL DEFAULT '{}',
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- character_progression : 성장 이력.
-- 레거시 대응 컬럼 — experience, dragonkills, dragonpoints, age, dragonage,
--                    bestdragonage, seendragon, seenmaster, seenbard, seenlover
-- -----------------------------------------------------------------------------
CREATE TABLE character_progression (
    character_id        INTEGER PRIMARY KEY,
    experience          INTEGER NOT NULL DEFAULT 0 CHECK (experience >= 0),
    dragon_kill_count   INTEGER NOT NULL DEFAULT 0 CHECK (dragon_kill_count >= 0),
    dragon_point_json   TEXT    NOT NULL DEFAULT '{}',
    game_age_day        INTEGER NOT NULL DEFAULT 0 CHECK (game_age_day >= 0),
    dragon_age_day      INTEGER NOT NULL DEFAULT 0 CHECK (dragon_age_day >= 0),
    best_dragon_age_day INTEGER NOT NULL DEFAULT 0 CHECK (best_dragon_age_day >= 0),
    has_seen_dragon     INTEGER NOT NULL DEFAULT 0 CHECK (has_seen_dragon IN (0, 1)),
    seen_master_level   INTEGER NOT NULL DEFAULT 0 CHECK (seen_master_level >= 0),
    has_seen_bard       INTEGER NOT NULL DEFAULT 0 CHECK (has_seen_bard IN (0, 1)),
    has_seen_lover      INTEGER NOT NULL DEFAULT 0 CHECK (has_seen_lover IN (0, 1)),
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

CREATE INDEX character_progression_experience_index ON character_progression (experience);

-- -----------------------------------------------------------------------------
-- character_specialty : 특기 계열과 사용 횟수.
-- 레거시 대응 컬럼 — specialty, darkarts/magic/thievery,
--                    darkartuses/magicuses/thieveryuses
-- 레거시 specialty 값: reference/common.php increment_specialty() —
--                      0 미선택, 1 Dark Arts, 2 Mystical Powers, 3 Thievery
-- -----------------------------------------------------------------------------
CREATE TABLE character_specialty (
    character_id        INTEGER PRIMARY KEY,
    specialty_code      INTEGER NOT NULL DEFAULT 0 CHECK (specialty_code IN (0, 1, 2, 3)),
    dark_arts_rank      INTEGER NOT NULL DEFAULT 0 CHECK (dark_arts_rank >= 0),
    mystical_power_rank INTEGER NOT NULL DEFAULT 0 CHECK (mystical_power_rank >= 0),
    thievery_rank       INTEGER NOT NULL DEFAULT 0 CHECK (thievery_rank >= 0),
    dark_arts_use       INTEGER NOT NULL DEFAULT 0 CHECK (dark_arts_use >= 0),
    mystical_power_use  INTEGER NOT NULL DEFAULT 0 CHECK (mystical_power_use >= 0),
    thievery_use        INTEGER NOT NULL DEFAULT 0 CHECK (thievery_use >= 0),
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- character_equipment : 착용 장비 스냅샷.
-- 레거시 대응 컬럼 — weapon, weaponvalue, weapondmg, armor, armorvalue, armordef, hashorse
-- 레거시는 이름 문자열을 계정 행에 직접 보관했으므로 그 형태를 유지하되,
-- 카탈로그 참조(weapon_id/armor_id/mount_id)를 추가해 정규화한다.
-- -----------------------------------------------------------------------------
CREATE TABLE character_equipment (
    character_id  INTEGER PRIMARY KEY,
    weapon_id     INTEGER,
    weapon_name   TEXT    NOT NULL DEFAULT 'Fists',
    weapon_value  INTEGER NOT NULL DEFAULT 0 CHECK (weapon_value >= 0),
    weapon_damage INTEGER NOT NULL DEFAULT 0,
    armor_id      INTEGER,
    armor_name    TEXT    NOT NULL DEFAULT 'T-Shirt',
    armor_value   INTEGER NOT NULL DEFAULT 0 CHECK (armor_value >= 0),
    armor_defense INTEGER NOT NULL DEFAULT 0,
    mount_id      INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- character_wealth : 재화.
-- 레거시 대응 컬럼 — gold, goldinbank, gems, bounty, bounties,
--                    transferredtoday, amountouttoday
-- goldinbank 는 ALTER 로 부호 있는 정수가 되었으므로(대출 허용) CHECK 를 두지 않는다.
-- -----------------------------------------------------------------------------
CREATE TABLE character_wealth (
    character_id        INTEGER PRIMARY KEY,
    gold                INTEGER NOT NULL DEFAULT 0 CHECK (gold >= 0),
    gold_in_bank        INTEGER NOT NULL DEFAULT 0,
    gem                 INTEGER NOT NULL DEFAULT 0 CHECK (gem >= 0),
    bounty_on_self      INTEGER NOT NULL DEFAULT 0 CHECK (bounty_on_self >= 0),
    bounty_set_today    INTEGER NOT NULL DEFAULT 0 CHECK (bounty_set_today >= 0),
    received_today      INTEGER NOT NULL DEFAULT 0 CHECK (received_today >= 0),
    transferred_today   INTEGER NOT NULL DEFAULT 0 CHECK (transferred_today >= 0),
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- character_daily_allowance : 하루 단위로 초기화되는 자원.
-- 레거시 대응 컬럼 — turns, playerfights, drunkenness, boughtroomtoday,
--                    usedouthouse, lastwebvote, lastmotd
-- -----------------------------------------------------------------------------
CREATE TABLE character_daily_allowance (
    character_id        INTEGER PRIMARY KEY,
    forest_turn         INTEGER NOT NULL DEFAULT 10 CHECK (forest_turn >= 0),
    player_fight        INTEGER NOT NULL DEFAULT 3 CHECK (player_fight >= 0),
    drunkenness         INTEGER NOT NULL DEFAULT 0 CHECK (drunkenness >= 0),
    bought_room_today   INTEGER NOT NULL DEFAULT 0 CHECK (bought_room_today IN (0, 1)),
    used_outhouse_today INTEGER NOT NULL DEFAULT 0 CHECK (used_outhouse_today IN (0, 1)),
    last_web_vote_date  TEXT,
    last_motd_seen_at   TEXT,
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- character_social : 사회적 관계와 자기소개.
-- 레거시 대응 컬럼 — marriedto, playerkills, pk, charm, charisma,
--                    bio, biotime, history, recentcomments
-- -----------------------------------------------------------------------------
CREATE TABLE character_social (
    character_id        INTEGER PRIMARY KEY,
    married_to_character_id INTEGER,
    player_kill_count   INTEGER NOT NULL DEFAULT 0 CHECK (player_kill_count >= 0),
    pvp_immunity_lost   INTEGER NOT NULL DEFAULT 0 CHECK (pvp_immunity_lost IN (0, 1)),
    charm               INTEGER NOT NULL DEFAULT 0 CHECK (charm >= 0),
    charisma            INTEGER NOT NULL DEFAULT 0 CHECK (charisma >= 0),
    biography           TEXT    NOT NULL DEFAULT '',
    biography_updated_at TEXT,
    history_json        TEXT    NOT NULL DEFAULT '[]',
    comments_seen_at    TEXT,
    pvp_flag_at         TEXT,
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE,
    FOREIGN KEY (married_to_character_id)
        REFERENCES game_character (character_id) ON DELETE SET NULL
);

-- -----------------------------------------------------------------------------
-- character_session_state : 요청 간 이월되는 휘발성 상태.
-- 레거시 대응 컬럼 — allowednavs, output, specialinc, specialmisc,
--                    gentime, gentimecount, gensize
-- 레거시는 이 값들을 accounts 행에 직렬화해 저장했다(reference/common.php saveuser()).
-- -----------------------------------------------------------------------------
CREATE TABLE character_session_state (
    character_id          INTEGER PRIMARY KEY,
    allowed_navigation_json TEXT  NOT NULL DEFAULT '{}',
    rendered_output       TEXT    NOT NULL DEFAULT '',
    special_include_name  TEXT    NOT NULL DEFAULT '',
    special_misc_json     TEXT    NOT NULL DEFAULT '{}',
    generation_time_total REAL    NOT NULL DEFAULT 0,
    generation_count      INTEGER NOT NULL DEFAULT 0,
    generation_byte_total INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (character_id) REFERENCES game_character (character_id) ON DELETE CASCADE
);

-- =============================================================================
-- 카탈로그 도메인
-- 마스터 데이터이므로 계정/캐릭터에 의존하지 않는다.
-- character_equipment.weapon_id / armor_id / mount_id 가 이 도메인을 참조한다.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- weapon : 무기 카탈로그.
-- 레거시 대응 — weapons(weaponid, weaponname, value, damage, level)
-- level 은 드래곤 킬 수 구간(0~12)을 뜻한다(reference/logd-0.9.7-create.sql 의
-- "WHERE: level=N" 덤프 블록, reference/weapons.php 의 구매 조건).
-- -----------------------------------------------------------------------------
CREATE TABLE weapon (
    weapon_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    weapon_name       TEXT    NOT NULL,
    price             INTEGER NOT NULL DEFAULT 0 CHECK (price >= 0),
    damage            INTEGER NOT NULL DEFAULT 1,
    dragon_kill_tier  INTEGER NOT NULL DEFAULT 0 CHECK (dragon_kill_tier >= 0)
);

CREATE INDEX weapon_tier_damage_index ON weapon (dragon_kill_tier, damage);

-- -----------------------------------------------------------------------------
-- armor : 방어구 카탈로그.
-- 레거시 대응 — armor(armorid, armorname, value, defense, level)
-- -----------------------------------------------------------------------------
CREATE TABLE armor (
    armor_id          INTEGER PRIMARY KEY AUTOINCREMENT,
    armor_name        TEXT    NOT NULL,
    price             INTEGER NOT NULL DEFAULT 0 CHECK (price >= 0),
    defense           INTEGER NOT NULL DEFAULT 1,
    dragon_kill_tier  INTEGER NOT NULL DEFAULT 0 CHECK (dragon_kill_tier >= 0)
);

CREATE INDEX armor_tier_defense_index ON armor (dragon_kill_tier, defense);

-- -----------------------------------------------------------------------------
-- creature : 숲/묘지 조우 크리처.
-- 레거시 대응 — creatures(creatureid, creaturename, creaturelevel, creatureweapon,
--               creaturelose, creaturewin, creaturegold, creatureexp,
--               creaturehealth, creatureattack, creaturedefense, oldcreatureexp,
--               createdby, location)
-- location: 0 숲, 1 묘지 (ALTER TABLE creatures ADD location)
-- -----------------------------------------------------------------------------
CREATE TABLE creature (
    creature_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    creature_name    TEXT    NOT NULL,
    creature_level   INTEGER NOT NULL DEFAULT 1 CHECK (creature_level >= 1),
    weapon_name      TEXT    NOT NULL DEFAULT '',
    victory_message  TEXT    NOT NULL DEFAULT '',
    defeat_message   TEXT    NOT NULL DEFAULT '',
    gold_reward      INTEGER NOT NULL DEFAULT 0 CHECK (gold_reward >= 0),
    experience_reward INTEGER NOT NULL DEFAULT 0 CHECK (experience_reward >= 0),
    health           INTEGER NOT NULL DEFAULT 1 CHECK (health >= 1),
    attack_point     INTEGER NOT NULL DEFAULT 0 CHECK (attack_point >= 0),
    defense_point    INTEGER NOT NULL DEFAULT 0 CHECK (defense_point >= 0),
    location_code    INTEGER NOT NULL DEFAULT 0 CHECK (location_code IN (0, 1)),
    created_by_name  TEXT    NOT NULL DEFAULT ''
);

CREATE INDEX creature_level_location_index ON creature (creature_level, location_code);

-- -----------------------------------------------------------------------------
-- training_master : 레벨업 도전 상대.
-- 레거시 대응 — masters(creatureid, creaturename, creaturelevel, creatureweapon,
--               creaturelose, creaturewin, creaturehealth, creatureattack,
--               creaturedefense)
-- 승리/패배 대사에는 레거시 치환 토큰 %W %w %X %x %p %s %o 가 그대로 들어 있다
-- (reference/train.php / reference/battle.php 가 해석한다).
-- -----------------------------------------------------------------------------
CREATE TABLE training_master (
    master_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    master_name     TEXT    NOT NULL,
    master_level    INTEGER NOT NULL CHECK (master_level >= 1),
    weapon_name     TEXT    NOT NULL DEFAULT '',
    victory_message TEXT    NOT NULL DEFAULT '',
    defeat_message  TEXT    NOT NULL DEFAULT '',
    health          INTEGER NOT NULL DEFAULT 1 CHECK (health >= 1),
    attack_point    INTEGER NOT NULL DEFAULT 0 CHECK (attack_point >= 0),
    defense_point   INTEGER NOT NULL DEFAULT 0 CHECK (defense_point >= 0),
    CONSTRAINT training_master_level_unique UNIQUE (master_level)
);

-- -----------------------------------------------------------------------------
-- mount : 탈것 카탈로그.
-- 레거시 대응 — mounts(mountid, mountname, mountdesc, mountcategory, mountbuff,
--               mountcostgems, mountcostgold, mountactive, mountforestfights,
--               tavern, newday, recharge, partrecharge,
--               mine_canenter, mine_candie, mine_cansave,
--               mine_tethermsg, mine_deathmsg, mine_savemsg)
-- mountbuff 는 PHP serialize 문자열이었으므로 JSON 으로 정규화한다.
-- mine_can_* 는 레거시에서 int(10) unsigned 확률값이며 0~100 으로 소비된다
-- (reference/special/goldmine.php 의 e_rand(1,100) 비교).
-- -----------------------------------------------------------------------------
CREATE TABLE mount (
    mount_id            INTEGER PRIMARY KEY AUTOINCREMENT,
    mount_name          TEXT    NOT NULL,
    mount_description   TEXT    NOT NULL DEFAULT '',
    mount_category      TEXT    NOT NULL DEFAULT '',
    buff_json           TEXT    NOT NULL DEFAULT '{}',
    cost_gem            INTEGER NOT NULL DEFAULT 0 CHECK (cost_gem >= 0),
    cost_gold           INTEGER NOT NULL DEFAULT 0 CHECK (cost_gold >= 0),
    is_active           INTEGER NOT NULL DEFAULT 1 CHECK (is_active IN (0, 1)),
    extra_forest_fight  INTEGER NOT NULL DEFAULT 0,
    tavern_access_level INTEGER NOT NULL DEFAULT 0 CHECK (tavern_access_level >= 0),
    new_day_message     TEXT    NOT NULL DEFAULT '',
    recharge_message    TEXT    NOT NULL DEFAULT '',
    partial_recharge_message TEXT NOT NULL DEFAULT '',
    mine_can_enter      INTEGER NOT NULL DEFAULT 0 CHECK (mine_can_enter BETWEEN 0 AND 100),
    mine_can_die        INTEGER NOT NULL DEFAULT 0 CHECK (mine_can_die BETWEEN 0 AND 100),
    mine_can_save       INTEGER NOT NULL DEFAULT 0 CHECK (mine_can_save BETWEEN 0 AND 100),
    mine_tether_message TEXT    NOT NULL DEFAULT '',
    mine_death_message  TEXT    NOT NULL DEFAULT '',
    mine_save_message   TEXT    NOT NULL DEFAULT ''
);

CREATE INDEX mount_category_active_index ON mount (mount_category, is_active);

-- -----------------------------------------------------------------------------
-- riddle : 수수께끼(스핑크스 이벤트).
-- 레거시 대응 — riddles(id, riddle, answer)
-- 정답은 세미콜론으로 구분된 복수 허용 표기였다("Salt", "The moon", "Eggs; oranges").
-- -----------------------------------------------------------------------------
CREATE TABLE riddle (
    riddle_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    riddle_text  TEXT NOT NULL,
    answer_text  TEXT NOT NULL
);

-- -----------------------------------------------------------------------------
-- taunt : PvP 승패 도발 문구.
-- 레거시 대응 — taunts(tauntid, taunt, editor)
-- 치환 토큰: %W 승자, %w 패자, %X 승자 무기, %x 패자 무기, %p 소유격,
--            %s 목적격, %o 주격 (reference/pvp.php / reference/battle.php)
-- -----------------------------------------------------------------------------
CREATE TABLE taunt (
    taunt_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    taunt_text  TEXT NOT NULL,
    editor_name TEXT NOT NULL DEFAULT ''
);

-- =============================================================================
-- 커뮤니티 도메인
-- 근거: reference/logd-0.9.7-create.sql 의 news, motd, commentary, mail,
--       petitions, pollresults 및 후속 ALTER.
-- =============================================================================

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

-- =============================================================================
-- 운영 도메인
-- 근거: reference/logd-0.9.7-create.sql 의 settings, bans, nastywords, logdnet,
--       referers, faillog, debuglog 및 ALTER(settings.value varchar(255),
--       referers.site).
-- =============================================================================

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

-- =============================================================================
-- 다국어 도메인
-- 레거시의 "원문 문자열 → 번역 문자열" 치환 방식을 "라벨 키 → 언어별 번역"
-- 구조로 대체한다.
-- 근거: reference/translator.php — 언어별 translator_<lang>.php 를 include 하고
--       페이지별 $replace 배열로 str_replace 치환. 원문이 바뀌면 번역이 통째로
--       끊기는 구조였으므로, 원문 대신 안정적인 키를 식별자로 삼는다.
--       reference/translator_generic.php 가 페이지 단위로 분기했던 것을
--       namespace 로 승격한다.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- locale : 지원 언어.
-- 프론트 src/shared/constant/lotdg-supported-locale.ts 의
-- LOTDG_SUPPORTED_LOCALE_CODE 와 동일한 코드 집합을 유지한다.
-- -----------------------------------------------------------------------------
CREATE TABLE locale (
    locale_code   TEXT PRIMARY KEY CHECK (locale_code IN ('en', 'ko', 'ja', 'zh', 'ru')),
    endonym       TEXT    NOT NULL,
    is_fallback   INTEGER NOT NULL DEFAULT 0 CHECK (is_fallback IN (0, 1)),
    sort_order    INTEGER NOT NULL DEFAULT 0
);

-- -----------------------------------------------------------------------------
-- label_key : 번역 대상 라벨의 식별자.
-- namespace 는 프론트 LOTDG_LOCALE_NAMESPACE 와 1:1 대응한다
-- (common, character-stat, navigation, authentication, village, forest,
--  battle, commerce, social, system-message).
-- source_reference 는 이 라벨이 유래한 레거시 위치를 남겨 포렌식 추적을 가능하게 한다.
-- -----------------------------------------------------------------------------
CREATE TABLE label_key (
    label_key_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    namespace_code   TEXT NOT NULL,
    label_path       TEXT NOT NULL,
    source_reference TEXT NOT NULL DEFAULT '',
    placeholder_json TEXT NOT NULL DEFAULT '[]',
    CONSTRAINT label_key_unique UNIQUE (namespace_code, label_path)
);

CREATE INDEX label_key_namespace_index ON label_key (namespace_code);

-- -----------------------------------------------------------------------------
-- label_translation : 라벨의 언어별 번역문.
-- translation_text 에는 레거시 색상코드(`^ `0 등)를 그대로 보존할 수 있다.
-- 번역이 없는 (키, 언어) 조합은 행이 없으며, 조회 계층이 폴백 언어로 대체한다.
-- -----------------------------------------------------------------------------
CREATE TABLE label_translation (
    label_key_id     INTEGER NOT NULL,
    locale_code      TEXT    NOT NULL,
    translation_text TEXT    NOT NULL,
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (label_key_id, locale_code),
    FOREIGN KEY (label_key_id) REFERENCES label_key (label_key_id) ON DELETE CASCADE,
    FOREIGN KEY (locale_code)  REFERENCES locale (locale_code)     ON DELETE CASCADE
);

CREATE INDEX label_translation_locale_index ON label_translation (locale_code);

-- -----------------------------------------------------------------------------
-- catalog_translation : 카탈로그 레코드(무기/방어구/크리처/마스터/탈것)의 이름·문구 번역.
-- 레거시는 이 데이터를 영어 문자열로 DB 에 직접 저장했으므로 번역 경로가 없었다.
-- entity_type/entity_id 로 대상을 지정하고, field_code 로 필드를 구분한다.
-- -----------------------------------------------------------------------------
CREATE TABLE catalog_translation (
    entity_type      TEXT    NOT NULL
                     CHECK (entity_type IN ('weapon', 'armor', 'creature',
                                            'training_master', 'mount', 'riddle', 'taunt')),
    entity_id        INTEGER NOT NULL,
    field_code       TEXT    NOT NULL,
    locale_code      TEXT    NOT NULL,
    translation_text TEXT    NOT NULL,
    updated_at       TEXT    NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (entity_type, entity_id, field_code, locale_code),
    FOREIGN KEY (locale_code) REFERENCES locale (locale_code) ON DELETE CASCADE
);

CREATE INDEX catalog_translation_locale_index ON catalog_translation (locale_code);
CREATE INDEX catalog_translation_entity_index ON catalog_translation (entity_type, entity_id);

-- -----------------------------------------------------------------------------
-- 지원 언어 시드. 폴백은 영어이며, 이는 reference/translator.php 의
-- "translator_<lang>.php 가 없으면 translator_en.php" 규칙과 동일하다.
-- -----------------------------------------------------------------------------
INSERT INTO locale (locale_code, endonym, is_fallback, sort_order) VALUES
    ('en', 'English',  1, 1),
    ('ko', '한국어',    0, 2),
    ('ja', '日本語',    0, 3),
    ('zh', '简体中文',  0, 4),
    ('ru', 'Русский',  0, 5);
