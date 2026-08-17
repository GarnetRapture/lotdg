-- =============================================================================
-- 0003_create_catalog_domain.sql
-- 게임 데이터 카탈로그(무기/방어구/크리처/마스터/탈것/수수께끼/도발)를 정의한다.
-- 선행: 없음 (마스터 데이터이므로 계정/캐릭터에 의존하지 않는다)
-- 소비: 0002 의 character_equipment.weapon_id / armor_id / mount_id 가 참조하며,
--       전투·상점 리포지토리가 조회한다.
-- 근거: reference/logd-0.9.7-create.sql 의 weapons, armor, creatures, masters,
--       mounts, riddles, taunts 테이블 정의와 후속 ALTER(level, location,
--       mountforestfights, tavern, newday, recharge, partrecharge, mine_*).
-- =============================================================================

PRAGMA foreign_keys = ON;

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
