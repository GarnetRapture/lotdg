-- =============================================================================
-- 0001_seed_character_stat_label.sql
-- character-stat 네임스페이스의 라벨 키와 5개 언어 번역을 적재한다.
-- 선행: 0006_create_localization_domain.sql (locale, label_key, label_translation)
-- 대응: src/i18n/locale/<code>/character-stat.json 과 키·값이 일치해야 한다.
-- 근거: reference/common.php charstats() 가 templatereplace() 에 넘기던 title 문자열.
--       한국어 값은 해당 함수의 EUC-KR 원문(기본 정보/아이디/체력/턴/영혼력/컨디션/
--       레벨/공격력/방어력/보석/그 외 정보/골드/경험치/무기/방어구/상태/없음) 그대로다.
-- =============================================================================

INSERT OR IGNORE INTO label_key (namespace_code, label_path, source_reference, placeholder_json)
VALUES
    ('character-stat', 'section.basic-information', 'reference/common.php charstats()', '[]'),
    ('character-stat', 'section.other-information', 'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.name',                'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.hit-point',           'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.turn',                'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.soul-point',          'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.grave-fight',         'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.spirit',              'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.level',               'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.attack',              'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.defence',             'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.gem',                 'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.gold',                'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.experience',          'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.weapon',              'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.armor',               'reference/common.php charstats()', '[]'),
    ('character-stat', 'field.buff',                'reference/common.php charstats()', '[]'),
    ('character-stat', 'spirit.dead',               'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'spirit.very-low',           'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'spirit.low',                'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'spirit.normal',             'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'spirit.high',               'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'spirit.very-high',          'reference/common.php charstats() $spirits', '[]'),
    ('character-stat', 'buff.none',                 'reference/common.php charstats()', '[]'),
    ('character-stat', 'buff.rounds-left',          'reference/common.php charstats()', '["rounds"]'),
    ('character-stat', 'online.header',             'reference/common.php charstats() 비로그인 분기', '[]'),
    ('character-stat', 'online.none',               'reference/common.php charstats() 비로그인 분기', '[]');

-- -----------------------------------------------------------------------------
-- 번역문. (namespace_code, label_path) 로 label_key_id 를 되찾아 넣는다.
-- -----------------------------------------------------------------------------
INSERT OR REPLACE INTO label_translation (label_key_id, locale_code, translation_text)
SELECT label_key.label_key_id, translation_source.locale_code, translation_source.translation_text
  FROM (
        SELECT 'section.basic-information' AS label_path, 'en' AS locale_code, 'Basic Information' AS translation_text
  UNION ALL SELECT 'section.other-information', 'en', 'Other Information'
  UNION ALL SELECT 'field.name',                'en', 'Name'
  UNION ALL SELECT 'field.hit-point',           'en', 'Hitpoints'
  UNION ALL SELECT 'field.turn',                'en', 'Turns'
  UNION ALL SELECT 'field.soul-point',          'en', 'Soul Points'
  UNION ALL SELECT 'field.grave-fight',         'en', 'Grave Fights'
  UNION ALL SELECT 'field.spirit',              'en', 'Spirits'
  UNION ALL SELECT 'field.level',               'en', 'Level'
  UNION ALL SELECT 'field.attack',              'en', 'Attack'
  UNION ALL SELECT 'field.defence',             'en', 'Defence'
  UNION ALL SELECT 'field.gem',                 'en', 'Gems'
  UNION ALL SELECT 'field.gold',                'en', 'Gold'
  UNION ALL SELECT 'field.experience',          'en', 'Experience'
  UNION ALL SELECT 'field.weapon',              'en', 'Weapon'
  UNION ALL SELECT 'field.armor',               'en', 'Armor'
  UNION ALL SELECT 'field.buff',                'en', 'Buffs'
  UNION ALL SELECT 'spirit.dead',               'en', 'Dead'
  UNION ALL SELECT 'spirit.very-low',           'en', 'Very Low'
  UNION ALL SELECT 'spirit.low',                'en', 'Low'
  UNION ALL SELECT 'spirit.normal',             'en', 'Normal'
  UNION ALL SELECT 'spirit.high',               'en', 'High'
  UNION ALL SELECT 'spirit.very-high',          'en', 'Very High'
  UNION ALL SELECT 'buff.none',                 'en', 'None'
  UNION ALL SELECT 'buff.rounds-left',          'en', '{rounds} rounds left'
  UNION ALL SELECT 'online.header',             'en', 'Players Online'
  UNION ALL SELECT 'online.none',               'en', 'Nobody'

  UNION ALL SELECT 'section.basic-information', 'ko', '기본 정보'
  UNION ALL SELECT 'section.other-information', 'ko', '그 외 정보'
  UNION ALL SELECT 'field.name',                'ko', '아이디'
  UNION ALL SELECT 'field.hit-point',           'ko', '체력'
  UNION ALL SELECT 'field.turn',                'ko', '턴'
  UNION ALL SELECT 'field.soul-point',          'ko', '영혼력'
  UNION ALL SELECT 'field.grave-fight',         'ko', '턴'
  UNION ALL SELECT 'field.spirit',              'ko', '컨디션'
  UNION ALL SELECT 'field.level',               'ko', '레벨'
  UNION ALL SELECT 'field.attack',              'ko', '공격력'
  UNION ALL SELECT 'field.defence',             'ko', '방어력'
  UNION ALL SELECT 'field.gem',                 'ko', '보석'
  UNION ALL SELECT 'field.gold',                'ko', '골드'
  UNION ALL SELECT 'field.experience',          'ko', '경험치'
  UNION ALL SELECT 'field.weapon',              'ko', '무기'
  UNION ALL SELECT 'field.armor',               'ko', '방어구'
  UNION ALL SELECT 'field.buff',                'ko', '상태'
  UNION ALL SELECT 'spirit.dead',               'ko', '죽음'
  UNION ALL SELECT 'spirit.very-low',           'ko', '매우 낮음'
  UNION ALL SELECT 'spirit.low',                'ko', '낮음'
  UNION ALL SELECT 'spirit.normal',             'ko', '보통'
  UNION ALL SELECT 'spirit.high',               'ko', '높음'
  UNION ALL SELECT 'spirit.very-high',          'ko', '매우 높음'
  UNION ALL SELECT 'buff.none',                 'ko', '없음'
  UNION ALL SELECT 'buff.rounds-left',          'ko', '{rounds}턴 남음'
  UNION ALL SELECT 'online.header',             'ko', '접속중인 사람'
  UNION ALL SELECT 'online.none',               'ko', '없음'

  UNION ALL SELECT 'section.basic-information', 'ja', '基本情報'
  UNION ALL SELECT 'section.other-information', 'ja', 'その他の情報'
  UNION ALL SELECT 'field.name',                'ja', '名前'
  UNION ALL SELECT 'field.hit-point',           'ja', '体力'
  UNION ALL SELECT 'field.turn',                'ja', 'ターン'
  UNION ALL SELECT 'field.soul-point',          'ja', '魂の力'
  UNION ALL SELECT 'field.grave-fight',         'ja', 'ターン'
  UNION ALL SELECT 'field.spirit',              'ja', 'コンディション'
  UNION ALL SELECT 'field.level',               'ja', 'レベル'
  UNION ALL SELECT 'field.attack',              'ja', '攻撃力'
  UNION ALL SELECT 'field.defence',             'ja', '防御力'
  UNION ALL SELECT 'field.gem',                 'ja', '宝石'
  UNION ALL SELECT 'field.gold',                'ja', 'ゴールド'
  UNION ALL SELECT 'field.experience',          'ja', '経験値'
  UNION ALL SELECT 'field.weapon',              'ja', '武器'
  UNION ALL SELECT 'field.armor',               'ja', '防具'
  UNION ALL SELECT 'field.buff',                'ja', '状態'
  UNION ALL SELECT 'spirit.dead',               'ja', '死亡'
  UNION ALL SELECT 'spirit.very-low',           'ja', '非常に低い'
  UNION ALL SELECT 'spirit.low',                'ja', '低い'
  UNION ALL SELECT 'spirit.normal',             'ja', '普通'
  UNION ALL SELECT 'spirit.high',               'ja', '高い'
  UNION ALL SELECT 'spirit.very-high',          'ja', '非常に高い'
  UNION ALL SELECT 'buff.none',                 'ja', 'なし'
  UNION ALL SELECT 'buff.rounds-left',          'ja', '残り{rounds}ターン'
  UNION ALL SELECT 'online.header',             'ja', '接続中のプレイヤー'
  UNION ALL SELECT 'online.none',               'ja', 'なし'

  UNION ALL SELECT 'section.basic-information', 'zh', '基本信息'
  UNION ALL SELECT 'section.other-information', 'zh', '其他信息'
  UNION ALL SELECT 'field.name',                'zh', '名称'
  UNION ALL SELECT 'field.hit-point',           'zh', '生命值'
  UNION ALL SELECT 'field.turn',                'zh', '回合'
  UNION ALL SELECT 'field.soul-point',          'zh', '灵魂力'
  UNION ALL SELECT 'field.grave-fight',         'zh', '回合'
  UNION ALL SELECT 'field.spirit',              'zh', '状态'
  UNION ALL SELECT 'field.level',               'zh', '等级'
  UNION ALL SELECT 'field.attack',              'zh', '攻击力'
  UNION ALL SELECT 'field.defence',             'zh', '防御力'
  UNION ALL SELECT 'field.gem',                 'zh', '宝石'
  UNION ALL SELECT 'field.gold',                'zh', '金币'
  UNION ALL SELECT 'field.experience',          'zh', '经验值'
  UNION ALL SELECT 'field.weapon',              'zh', '武器'
  UNION ALL SELECT 'field.armor',               'zh', '护甲'
  UNION ALL SELECT 'field.buff',                'zh', '增益'
  UNION ALL SELECT 'spirit.dead',               'zh', '死亡'
  UNION ALL SELECT 'spirit.very-low',           'zh', '非常低'
  UNION ALL SELECT 'spirit.low',                'zh', '低'
  UNION ALL SELECT 'spirit.normal',             'zh', '普通'
  UNION ALL SELECT 'spirit.high',               'zh', '高'
  UNION ALL SELECT 'spirit.very-high',          'zh', '非常高'
  UNION ALL SELECT 'buff.none',                 'zh', '无'
  UNION ALL SELECT 'buff.rounds-left',          'zh', '剩余{rounds}回合'
  UNION ALL SELECT 'online.header',             'zh', '在线玩家'
  UNION ALL SELECT 'online.none',               'zh', '无'

  UNION ALL SELECT 'section.basic-information', 'ru', 'Основные сведения'
  UNION ALL SELECT 'section.other-information', 'ru', 'Прочие сведения'
  UNION ALL SELECT 'field.name',                'ru', 'Имя'
  UNION ALL SELECT 'field.hit-point',           'ru', 'Здоровье'
  UNION ALL SELECT 'field.turn',                'ru', 'Ходы'
  UNION ALL SELECT 'field.soul-point',          'ru', 'Очки души'
  UNION ALL SELECT 'field.grave-fight',         'ru', 'Ходы'
  UNION ALL SELECT 'field.spirit',              'ru', 'Состояние духа'
  UNION ALL SELECT 'field.level',               'ru', 'Уровень'
  UNION ALL SELECT 'field.attack',              'ru', 'Атака'
  UNION ALL SELECT 'field.defence',             'ru', 'Защита'
  UNION ALL SELECT 'field.gem',                 'ru', 'Самоцветы'
  UNION ALL SELECT 'field.gold',                'ru', 'Золото'
  UNION ALL SELECT 'field.experience',          'ru', 'Опыт'
  UNION ALL SELECT 'field.weapon',              'ru', 'Оружие'
  UNION ALL SELECT 'field.armor',               'ru', 'Броня'
  UNION ALL SELECT 'field.buff',                'ru', 'Эффекты'
  UNION ALL SELECT 'spirit.dead',               'ru', 'Смерть'
  UNION ALL SELECT 'spirit.very-low',           'ru', 'Очень низкое'
  UNION ALL SELECT 'spirit.low',                'ru', 'Низкое'
  UNION ALL SELECT 'spirit.normal',             'ru', 'Обычное'
  UNION ALL SELECT 'spirit.high',               'ru', 'Высокое'
  UNION ALL SELECT 'spirit.very-high',          'ru', 'Очень высокое'
  UNION ALL SELECT 'buff.none',                 'ru', 'Нет'
  UNION ALL SELECT 'buff.rounds-left',          'ru', 'осталось ходов: {rounds}'
  UNION ALL SELECT 'online.header',             'ru', 'Игроки в сети'
  UNION ALL SELECT 'online.none',               'ru', 'Никого'
       ) AS translation_source
  JOIN label_key
    ON label_key.namespace_code = 'character-stat'
   AND label_key.label_path     = translation_source.label_path;
