-- =============================================================================
-- 0002_create_character_domain.sql
-- 레거시 accounts 단일 테이블에서 "캐릭터(스탯/전투/자산/일일상태)" 책임을 분리한다.
-- 선행: 0001_create_account_domain.sql (account 테이블이 존재해야 한다)
-- 근거: reference/logd-0.9.7-create.sql 의 accounts 컬럼 및 후속 ALTER,
--       reference/common.php charstats()/$titles/$races, reference/train.php 의 레벨 규칙.
-- =============================================================================

PRAGMA foreign_keys = ON;

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
