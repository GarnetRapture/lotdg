<div align="center">

<img src="public/asset/legacy/image/title-banner.gif" alt="Legend of the Green Dragon" width="395">

<img src="public/asset/legacy/image/scroll-upper.gif" alt="" width="273">

**한국어 · English · 日本語 · 简体中文 · Русский**

PHP4 기반 웹 텍스트 RPG **Legend of the Green Dragon 0.9.7+jt** 한국어판을<br>
원본 동작과 동일하게 재구현한 프로젝트다.

<img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9">
<img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3">
<img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7">
<img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19">
<img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8">
<img src="https://img.shields.io/badge/License-GPL--2.0--only-4C1?logo=gnu&logoColor=white" alt="GPL-2.0-only">

<img src="public/asset/legacy/image/scroll-lower.gif" alt="" width="273">

</div>

---

## 게임 진행 구성

<img src="public/asset/legacy/image/login-dragon.gif" alt="" align="right" width="180">

플레이어 캐릭터는 트롤, 엘프, 인간, 드워프 중 하나의 종족으로 생성되며, 초기 위치는 마을
광장이다. 캐릭터는 무기 상점 및 방어구 상점에서 장비를 취득한 후 숲으로 진입한다.

숲에서 크리처와 조우할 때마다 숲 턴이 1 차감된다. 조우 1회당 1/7 확률로 크리처 대신 특수
사건이 발생하고, 승리 시 1/25 확률로 보석 1개가 추가 지급된다. 승리하면 골드와 경험치가
지급되며, 해당 전투에서 피격이 없는 경우 상기 숲 턴이 1 또는 2 환급된다. 패배하면 보유
골드가 전액 소멸하고, 경험치는 기존값의 90%로 감소하며, 캐릭터는 사망 상태로 전환된다.

숲 턴을 모두 소진한 캐릭터는 여관에서 에일을 구매하거나 방을 빌린다. 새 날이 개시되면
체력과 숲 턴이 재설정된다. 상기 숲 턴 수치는 그날의 기력, 탈것, 직전일의 취기, 원혼
부착 여부에 따라 가감된다. 캐릭터는 레벨을 상승시켜 훈련소 마스터의 인정을 받고, 최종적으로
성에서 녹색 용과 대전한다.

### 일일 갱신 규칙

| 대상        | 처리 내용                                                                   |
| ----------- | --------------------------------------------------------------------------- |
| 체력        | 최대 체력으로 회복하고 사망 상태를 해제한다                                 |
| 숲 턴       | 기본 턴 수에 그날의 기력(−2 ~ +2)과 드래곤 포인트 추가 전투 수를 더한다     |
| 종족 보정   | 종족이 인간인 경우 숲 턴을 1 가산한다                                       |
| 탈것 보정   | 탈것을 보유한 경우 해당 탈것의 추가 전투 수만큼 숲 턴을 가산한다            |
| 숙취 보정   | 취기가 66을 초과한 경우 숲 턴을 1 차감한다                                  |
| 원혼 보정   | 원혼이 부착된 경우 숲 턴을 1 차감한다                                       |
| 은행 이자   | 직전일 숲 턴 잔량이 기준치 이하인 경우 예치금에 이자를 가산한다             |
| 특기 사용   | 어둠의 마법, 신비한 힘, 도둑 기술의 일일 사용 횟수를 숙련도에 따라 산정한다 |
| 일일 상태값 | 플레이어 대전 횟수, 뒷간 이용 여부, 방값 지불 여부를 초기화한다             |

## 지역 및 화면 구성

<table>
<tr>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-village.gif" alt="" width="180"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-forest.gif" alt="" width="240"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-castle.gif" alt="" width="150"></td>
</tr>
<tr>
<td align="center"><b>마을 광장</b></td>
<td align="center"><b>어두운 숲</b></td>
<td align="center"><b>성</b></td>
</tr>
</table>

좌측 항해 막대는 4개 묶음으로 구성되며, 각 묶음에 속한 화면은 다음과 같다.

| 묶음 | 화면                                                                                                                    |
| ---- | ----------------------------------------------------------------------------------------------------------------------- |
| 전투 | 숲, 전사 훈련소, 플레이어 대전, 녹색 용, 묘지, 현상금 게시판                                                            |
| 상점 | 무기 상점, 방어구 상점, 은행, 마구간, 보석 상인, 치유사                                                                 |
| 마을 | 마을 광장, 여관, 정원, 베테랑 클럽, 집시 점술사, 뒷간                                                                   |
| 기타 | 오늘의 소식, 공지, 우편, 전사 명부, 명예의 전당, 환경 설정, 운영 문의, 게임 안내, 관리 메뉴, 무기 편집기, 방어구 편집기 |

### 특수 사건 구성

크리처 조우를 대체하는 특수 사건은 16종이다. 보상을 즉시 지급하고 종료하는 유형과, 전용
화면으로 전환하여 선택 입력을 요구하는 유형으로 구분된다.

| 사건                           | 처리 내용                                                     |
| ------------------------------ | ------------------------------------------------------------- |
| 보석 줍기 · 금화 줍기          | 보상을 즉시 지급하고 종료한다                                 |
| 고운 노인 · 흉한 노인          | 대가 없이 보상을 지급하고 종료한다                            |
| 노인의 내기 · 마을로 가는 노인 | 내기 수락 여부 또는 이동 여부를 선택 입력으로 받는다          |
| 빛나는 시내 · 요정 · 풀밭      | 전용 화면에서 상호작용을 처리한다                             |
| 수수께끼                       | 정답 입력 시 보상을 지급하고, 오답 시 보상 없이 종료한다      |
| 미친 오드리 · 금광             | 위험 감수를 선택하면 보상 기댓값이 증가한다                   |
| 스킬 마스터                    | 특기 숙련도를 판정하여 숙련도를 상승시킨다                    |
| 조난 · 강령술사                | 수락 시 대가와 보상이 함께 적용되고, 거절 시 상태 변경이 없다 |
| 다크호스 주점                  | 전용 화면으로 전환하여 주점 상호작용을 처리한다               |

## 기술 구성

| 구분       | 스택                                                                                                                                                                                                                                                                                                                                                                                                        |
| ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 백엔드     | <img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9"> <img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3"> <img src="https://img.shields.io/badge/Composer-885630?logo=composer&logoColor=white" alt="Composer">                                                                                                |
| 프론트엔드 | <img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7"> <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19"> <img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8"> <img src="https://img.shields.io/badge/Zod-4-3E67B1?logo=zod&logoColor=white" alt="Zod 4"> |
| 검사 도구  | <img src="https://img.shields.io/badge/ESLint-10-4B32C3?logo=eslint&logoColor=white" alt="ESLint 10"> <img src="https://img.shields.io/badge/Prettier-3.9-F7B93E?logo=prettier&logoColor=black" alt="Prettier 3.9">                                                                                                                                                                                         |

- **백엔드** — PHP 8.5.9, PSR-4(`Lotdg\` → `api/src/`), SQLite 3(PDO), Composer
- **프론트엔드** — TypeScript 7, React 19, Vite 8, Zod 4
- **검사 도구** — ESLint 10, Prettier 3.9, `tsc -b`, `php -l`
- **다국어** — 한국어 · 영어 · 일본어 · 중국어(간체) · 러시아어, JSON 라벨과 DB 라벨 테이블을 병행한다
- **화면** — 레거시 `yarbrough` 템플릿에서 측정한 색상과 치수를 토큰으로 고정하고, 8K까지 비율을 유지한다
- **라이선스** — GPL-2.0-only ([LICENSE](LICENSE), [NOTICE.md](NOTICE.md), [AUTHORS.md](AUTHORS.md))

레거시 원본은 `reference/` 에 보존하며 대조 근거로만 사용한다. 코드는 이식하지 않고 전부
신규 작성한다.

## 디렉터리 구조

```
api/                            PHP 백엔드
  bin/migrate.php               마이그레이션 적용 CLI
  bin/import-legacy-catalog.php 레거시 덤프 기반 카탈로그 적재 CLI
  config/application.php        경로·환경 설정의 단일 정의처
  database/
    migration/                  스키마 정의
    seed/                       스탯 라벨·관리자 계정 초기 데이터
    storage/                    SQLite 파일 (git 제외)
  public/index.php              HTTP 진입점
  src/
    Kernel/                     요청 수명주기
    Http/                       라우트 표, 응답, 컨트롤러, 미들웨어
    Persistence/                SQLite 연결·마이그레이션·리포지토리
    Domain/                     도메인 로직 (Account, Catalog, Social, World)
    I18n/                       다국어 처리
    Support/                    공용 유틸

src/                            프론트엔드
  app/                          루트 컴포넌트, 셸 레이아웃, 화면 코드 표
  feature/                      도메인별 화면 (village, forest, battle, commerce, social, ...)
  shared/
    schema/                     Zod 스키마 (DB 계약과 1:1 대응)
    constant/                   색상코드·로케일 등 단일 정의처
    type/  lib/  ui/
  i18n/locale/<code>/           언어별 라벨 JSON
  style/                        디자인 토큰·레거시 색상·레이아웃

public/asset/legacy/image/      레거시 이미지 자산
reference/                      PHP4 원본 (대조용, 배포 제외)
```

## 데이터베이스 구성

레거시는 `accounts` 단일 테이블에 100개 이상의 컬럼을 배치하여 계정 정보와 캐릭터 정보를
혼재시켰다. 본 재구현은 `api/database/migration/0001_create_schema.sql` 단일 파일에 책임별로
분해한 40개 테이블을 정의한다.

| 영역     | 테이블                                                                                                                                                                                                                              |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 계정     | `account`, `account_privilege`, `account_device_fingerprint`, `account_preference`, `account_donation`, `account_referral`                                                                                                          |
| 캐릭터   | `game_character`, `character_vital`, `character_combat_stat`, `character_progression`, `character_specialty`, `character_equipment`, `character_wealth`, `character_daily_allowance`, `character_social`, `character_session_state` |
| 카탈로그 | `weapon`, `armor`, `creature`, `training_master`, `mount`, `riddle`, `taunt`                                                                                                                                                        |
| 커뮤니티 | `daily_news`, `message_of_the_day`, `poll_result`, `commentary`, `mail_message`, `petition`                                                                                                                                         |
| 운영     | `game_setting`, `access_ban`, `nasty_word`, `logdnet_server`, `referer_hit`, `login_failure_log`, `debug_log`                                                                                                                       |
| 다국어   | `locale`, `label_key`, `label_translation`, `catalog_translation`                                                                                                                                                                   |

레거시가 `PHP serialize()` 로 저장하던 필드(`prefs`, `bufflist`, `dragonpoints`,
`donationconfig`, `mountbuff` 등)는 전부 JSON 컬럼으로 전환한다. 초기 데이터는
`database/seed/` 의 스탯 라벨과 관리자 계정 2건을 적재한다.

## 다국어 구성

레거시 `translator.php` 는 언어별 PHP 파일을 include 한 후 **원문 문자열 자체를 키로 사용하여**
`str_replace` 로 치환한다. 상기 방식은 원문이 1자라도 변경되면 번역이 적용되지 않는다.

본 재구현은 `(namespace, label_path)` 쌍을 키로 사용하며, 동일 키 체계를 다음 두 경로가 공유한다.

- 정적 리소스 — `src/i18n/locale/<code>/<namespace>.json`
  (`common`, `navigation`, `authentication`, `character-stat`, `village`, `forest`, `battle`,
  `commerce`, `social`, `system-message` 10종)
- 동적 조회 — `label_key` · `label_translation` 테이블 및 `GET /api/locale/{locale_code}`

번역이 존재하지 않는 키는 폴백 언어인 영어(`en`)로 대체한다. 상기 규칙은 레거시가
`translator_<lang>.php` 부재 시 `translator_en.php` 로 복귀하던 동작과 동일하다.

## 화면 구성

`src/style/lotdg-design-token.css` 는 레거시 `yarbrough` 템플릿에서 측정한 색상과 치수를 토큰으로
보관한다. 레이아웃은 원본 픽셀값을 직접 참조하지 않고 `--lotdg-scale-factor` 에서 파생된 토큰만
참조하므로, 배율이 변경되어도 비율이 유지된다.

| 뷰포트       | 배율 |
| ------------ | ---- |
| ~1279px      | 1    |
| 1280px (HD)  | 1    |
| 1920px (FHD) | 1.5  |
| 2560px (QHD) | 2    |
| 3200px (3K)  | 2.5  |
| 3840px (4K)  | 3    |
| 5120px (5K)  | 4    |
| 6016px (6K)  | 4.5  |
| 7680px (8K)  | 6    |

뷰포트 폭이 720px 이하인 경우 좌측 레일을 본문 상단으로 이동시킨다.

레거시 출력 색상코드(`` `^ ``, `` `0 `` 등 16색)는 `src/shared/constant/lotdg-legacy-color-code.ts`
가 CSS 클래스로 매핑하고, `src/style/lotdg-legacy-color-class.css` 가 토큰으로 구현한다.
클래스명(`colDkBlue` 등)은 레거시와 동일하게 유지한다.

### 이미지 자산 배치

`public/asset/legacy/image/` 의 GIF는 원본 자산이며, 각 자산의 적용 위치는 다음과 같다.

| 자산                                                                                                        | 적용 위치                                            |
| ----------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| <img src="public/asset/legacy/image/title-banner.gif" width="150">                                          | 셸 머리말의 제목 배너 (`LotdgShellLayout`)           |
| <img src="public/asset/legacy/image/scroll-upper.gif" width="120">                                          | 좌측 레일 패널 상단 마구리                           |
| <img src="public/asset/legacy/image/scroll-lower.gif" width="120">                                          | 좌측 레일 패널 하단 마구리                           |
| <img src="public/asset/legacy/image/header-background.gif" width="150">                                     | 머리말 배경 (`--lotdg-asset-header-background`)      |
| <img src="public/asset/legacy/image/footer-rule.gif" width="150">                                           | 꼬리말 구분선 (`--lotdg-asset-footer-rule`)          |
| <img src="public/asset/legacy/image/login-dragon.gif" width="90">                                           | 로그인 화면의 용 고리 (`--lotdg-asset-login-dragon`) |
| <img src="public/asset/legacy/image/scene-village.gif" width="80">                                          | 마을 장면 (`--lotdg-asset-scene-village`)            |
| <img src="public/asset/legacy/image/scene-forest.gif" width="120">                                          | 숲 장면 (`--lotdg-asset-scene-forest`)               |
| <img src="public/asset/legacy/image/scene-castle.gif" width="70">                                           | 성 장면 (`--lotdg-asset-scene-castle`)               |
| `marker-new.gif` · `scroll-new.gif` · `scroll-old.gif` · `signature-mightye.gif` · `spacer-transparent.gif` | 원본 보존 자산이며, 현재 화면에서 참조하지 않는다    |

## 개발

### 준비

```bash
npm install
cd api && composer install && cd ..
npm run migrate                          # api/bin/migrate.php — SQLite 스키마 적용
php api/bin/import-legacy-catalog.php    # 레거시 덤프에서 무기·방어구·크리처 등을 적재
```

카탈로그 적재 CLI는 인자를 지정하지 않으면 `reference/logd-0.9.7-create.sql` 을 읽고, 적재한
테이블별 건수를 출력한다. 다른 덤프를 사용하는 경우 해당 파일 경로를 인자로 지정한다.

### 실행

`npm run dev` 는 프론트엔드와 PHP 백엔드를 동시에 기동한다. Vite 개발 서버는 `/api` 요청을
`127.0.0.1:8080` 의 PHP 내장 서버로 프록시한다.

```bash
npm run dev           # web + api 동시 실행 (concurrently)
npm run dev:web       # vite 단독
npm run dev:api       # php -S 127.0.0.1:8080 -t api/public api/public/router.php
```

`api/public/router.php` 는 내장 서버 전용 폴백이다. 실제 배포 환경에서는 웹 서버의 재작성
규칙이 상기 역할을 대체하므로 `api/public/index.php` 만 진입점으로 지정한다.

### 빌드

```bash
npm run build         # build:web + build:api
npm run build:web     # tsc -b && vite build     → dist/
npm run build:api     # node scripts/build-api.mjs → dist/api/
```

`build:api` 는 `src` · `public` · `config` · `bin` · `database/migration` · `database/seed` ·
`composer.json` 만 `dist/api/` 로 복사한 후, 해당 경로에서 `composer install --no-dev
--optimize-autoloader` 를 실행한다. `reference/` 와 SQLite 파일은 산출물에서 제외한다.

### 검사

```bash
npm run check         # typecheck + lint + format:check
npm run typecheck     # tsc -b
npm run lint          # eslint
npm run lint:php      # api 안에서 composer run lint (php -l 전체)
npm run format:check  # prettier
```

검증은 정적 분석, 타입 검사, 코드 리뷰로만 수행한다. 개발 서버 기동 또는 빌드 실행을 통한
오류 확인은 검증 수단으로 사용하지 않는다.

## 라이선스

GPL-2.0-only 를 적용한다. 원저작자 Eric Stevens 및 JT(Joe Naylor), 레거시 한국어화 담당
xc8oa 및 digirave, 본 재구현의 포팅자 GarnetRapture 의 기여를 [AUTHORS.md](AUTHORS.md) 에
명시한다. 법적 효력을 갖는 문서는 [LICENSE](LICENSE) 의 영문 원문이며, [NOTICE.md](NOTICE.md)
는 5개 언어 안내 고지다.

<div align="center">

<img src="public/asset/legacy/image/footer-rule.gif" alt="" width="400">

Copyright 2002-2006 Eric Stevens &amp; JT · Ported by GarnetRapture · GPL-2.0-only

</div>
