-- =============================================================================
-- 0006_create_localization_domain.sql
-- 다국어 라벨 정규화 도메인. 레거시의 "원문 문자열 → 번역 문자열" 치환 방식을
-- "라벨 키 → 언어별 번역" 구조로 대체한다.
-- 선행: 없음 (마스터 데이터)
-- 소비: I18n 리포지토리 및 라벨 응답 엔드포인트. 프론트 src/i18n/locale/<code>/
--       JSON 과 동일한 (namespace, key) 체계를 공유한다.
-- 근거: reference/translator.php — 언어별 translator_<lang>.php 를 include 하고
--       페이지별 $replace 배열로 str_replace 치환. 원문이 바뀌면 번역이 통째로
--       끊기는 구조였으므로, 원문 대신 안정적인 키를 식별자로 삼는다.
--       reference/translator_generic.php 가 페이지 단위로 분기했던 것을
--       namespace 로 승격한다.
-- =============================================================================

PRAGMA foreign_keys = ON;

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
