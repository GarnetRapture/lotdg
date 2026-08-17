-- =============================================================================
-- 0002_seed_administrator_account.sql
-- 최초 관리자 계정. 레거시 대응 —
--   reference/logd-0.9.7-create.sql:76
--   INSERT INTO accounts (login,name,password,superuser,laston)
--   VALUES ("ADMIN","ADMIN","CHANGEME",3,now());
-- 레거시와 동일하게 평문 비밀번호로 심는다. Lotdg\Support\PasswordHasher 는
-- 해시가 아닌 저장값을 평문 비교로 통과시키고 needsRehash 로 true 를 돌려주므로,
-- 최초 로그인 시점에 PASSWORD_DEFAULT 해시로 자동 교체된다.
-- 계정/캐릭터의 부속 행 구성은 Lotdg\Persistence\Repository\AccountRepository
-- 및 CharacterRepository 가 신규 가입에서 만드는 것과 동일하다.
-- =============================================================================

INSERT OR IGNORE INTO account (login_name, password_hash, email_address, email_validation_key, email_validated)
VALUES ('ADMIN', 'CHANGEME', '', '', 1);

INSERT OR IGNORE INTO account_privilege (account_id, superuser_level)
SELECT account_id, 3 FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO account_preference (account_id, locale_code)
SELECT account_id, 'en' FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO account_device_fingerprint (account_id)
SELECT account_id FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO account_donation (account_id)
SELECT account_id FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO account_referral (account_id)
SELECT account_id FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO game_character (account_id, display_name, sex_code, race_code, rank_title)
SELECT account_id, 'ADMIN', 0, 0, '' FROM account WHERE login_name = 'ADMIN';

INSERT OR IGNORE INTO character_vital (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_combat_stat (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_progression (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_specialty (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_equipment (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_wealth (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_daily_allowance (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_social (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';

INSERT OR IGNORE INTO character_session_state (character_id)
SELECT character_id FROM game_character WHERE display_name = 'ADMIN';
