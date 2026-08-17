# LoTDG — Legend of the Green Dragon, reimplemented

PHP4 시절의 **Legend of the Green Dragon 0.9.7+jt** 한국어판을 원본 동작에 맞춰 재구현한다.
레거시 원본은 `reference/` 에 두고 대조 근거로만 쓰며, 코드는 새로 작성한다.

- **백엔드** — PHP 8.5.9, PSR-4(`Lotdg\` → `api/src/`), SQLite 3 (PDO), Composer
- **프론트엔드** — TypeScript 7, React 19, Vite 8, Zod 4
- **다국어** — 영어 / 한국어 / 일본어 / 중국어(간체) / 러시아어, JSON 라벨 + DB 라벨 테이블
- **디자인** — 레거시 `yarbrough` 템플릿의 팔레트·치수를 토큰으로 고정, 8K까지 반응형
- **라이선스** — GPL-2.0-only ([LICENSE](LICENSE), [NOTICE.md](NOTICE.md), [AUTHORS.md](AUTHORS.md))

## 디렉터리 구조

```
api/                          PHP 백엔드
  bin/migrate.php             마이그레이션 적용 CLI
  config/application.php      경로·환경 설정의 단일 출처
  database/
    migration/                0001~0006 스키마 정의
    seed/                     라벨 등 초기 데이터
    storage/                  SQLite 파일 (git 제외)
  public/index.php            HTTP 진입점
  src/
    Kernel/                   요청 수명주기
    Http/                     라우트 표, 응답, 컨트롤러
    Persistence/              SQLite 연결·마이그레이션·리포지토리
    Domain/                   도메인 로직
    I18n/                     다국어 처리
    Support/                  공용 유틸

src/                          프론트엔드
  app/                        루트 컴포넌트와 셸 레이아웃
  feature/                    도메인별 화면 (village, forest, battle, ...)
  shared/
    schema/                   Zod 스키마 (DB 계약과 1:1)
    constant/                 색상코드·로케일 등 단일 정의처
    type/  lib/  ui/
  i18n/locale/<code>/         언어별 라벨 JSON
  style/                      디자인 토큰·레거시 색상·레이아웃

public/asset/legacy/image/    레거시 이미지 자산
reference/                    PHP4 원본 (대조용, 배포 제외)
```

## 데이터베이스

레거시는 `accounts` 단일 테이블에 100개가 넘는 컬럼을 몰아넣었다. 이를 책임별로 분해했다.

| 마이그레이션                      | 내용                                                            |
| --------------------------------- | --------------------------------------------------------------- |
| `0001_create_account_domain`      | 계정, 권한, 단말 지문, 환경설정, 후원, 추천                     |
| `0002_create_character_domain`    | 캐릭터 본체, 생명, 전투, 성장, 특기, 장비, 재화, 일일자원, 사회 |
| `0003_create_catalog_domain`      | 무기, 방어구, 크리처, 마스터, 탈것, 수수께끼, 도발              |
| `0004_create_community_domain`    | 뉴스, 공지, 설문, 댓글, 우편, 청원                              |
| `0005_create_operation_domain`    | 설정, 차단, 금칙어, LoGDnet, 유입, 로그, 적용 이력              |
| `0006_create_localization_domain` | 언어, 라벨 키, 라벨 번역, 카탈로그 번역                         |

레거시가 `PHP serialize()` 로 저장하던 필드(`prefs`, `bufflist`, `dragonpoints`, `donationconfig`,
`mountbuff` 등)는 JSON 컬럼으로 바꿨다.

## 다국어 구조

레거시 `translator.php` 는 언어마다 PHP 파일을 include 하고 **원문 문자열을 키 삼아**
`str_replace` 로 치환했다. 원문이 한 글자만 바뀌어도 번역이 끊기는 구조였다.

본 재구현은 `(namespace, label_path)` 를 키로 쓴다. 같은 키 체계를 두 곳이 공유한다.

- 정적 리소스 — `src/i18n/locale/<code>/<namespace>.json`
- 동적 조회 — `label_key` / `label_translation` 테이블, `GET /api/locale/{locale_code}`

번역이 없는 키는 폴백 언어(`en`)로 대체한다. 이는 레거시가 `translator_<lang>.php` 부재 시
`translator_en.php` 로 되돌아가던 규칙과 같다.

## 디자인

`src/style/lotdg-design-token.css` 가 레거시 `yarbrough` 템플릿에서 측정한 색과 치수를 토큰으로
보관한다. 레이아웃은 원본 픽셀값을 직접 쓰지 않고 `--lotdg-scale-factor` 하나로 파생된 토큰만
참조하므로, 배율이 바뀌어도 비율이 어긋나지 않는다.

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

720px 이하에서는 좌측 레일을 본문 위로 접는다.

레거시 출력 색상코드(`` `^ ``, `` `0 `` 등 16색)는 `src/shared/constant/lotdg-legacy-color-code.ts`
가 CSS 클래스로 매핑하고, `src/style/lotdg-legacy-color-class.css` 가 토큰으로 구현한다.
클래스명(`colDkBlue` 등)은 레거시와 동일하게 유지한다.

## 개발

### 준비

```bash
npm install
cd api && composer install && cd ..
npm run migrate       # api/bin/migrate.php — SQLite 스키마 적용
```

### 실행

`npm run dev` 하나로 프론트엔드와 PHP 백엔드가 함께 뜬다. Vite 개발 서버는 `/api` 요청을
`127.0.0.1:8080` 의 PHP 내장 서버로 프록시한다.

```bash
npm run dev           # web + api 동시 실행 (concurrently)
npm run dev:web       # vite 단독
npm run dev:api       # php -S 127.0.0.1:8080 -t api/public api/public/router.php
```

`api/public/router.php` 는 내장 서버 전용 폴백이다. 실제 배포에서는 웹 서버의 재작성 규칙이
그 역할을 대신하므로 `api/public/index.php` 만 진입점으로 두면 된다.

### 빌드

```bash
npm run build         # build:web + build:api
npm run build:web     # tsc -b && vite build     → dist/
npm run build:api     # node scripts/build-api.mjs → dist/api/
```

`build:api` 는 `src` · `public` · `config` · `bin` · `database/migration` · `database/seed` ·
`composer.json` 만 `dist/api/` 로 옮기고, 그 안에서 `composer install --no-dev
--optimize-autoloader` 를 실행한다. `reference/` 와 SQLite 파일은 산출물에 포함되지 않는다.

### 검사

```bash
npm run check         # typecheck + lint + format:check
npm run typecheck     # tsc -b
npm run lint          # eslint
npm run lint:php      # api 내 composer run lint (php -l 전체)
npm run format:check  # prettier
```

정적 분석·타입 검사·코드 리뷰로 검증한다. 개발 서버나 빌드 실행은 검증 수단으로 쓰지 않는다.

## 라이선스

GPL-2.0-only. 원저작자 Eric Stevens, JT(Joe Naylor)와 레거시 한국어화 xc8oa, digirave,
그리고 본 재구현의 포팅자 GarnetRapture의 기여를 [AUTHORS.md](AUTHORS.md) 에 명시한다.
법적 효력을 갖는 것은 [LICENSE](LICENSE) 의 영문 원문이며, [NOTICE.md](NOTICE.md) 는
5개 언어 안내 고지다.
