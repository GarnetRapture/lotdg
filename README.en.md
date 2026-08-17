<div align="center">

<img src="public/asset/legacy/image/title-banner.gif" alt="Legend of the Green Dragon" width="395">

<img src="public/asset/legacy/image/scroll-upper.gif" alt="" width="273">

[한국어](README.md) · **English** · [日本語](README.ja.md) · [简体中文](README.zh.md) · [Русский](README.ru.md)

A reimplementation of the Korean edition of **Legend of the Green Dragon 0.9.7+jt**,<br>
a PHP4 web-based text RPG, reproducing the behavior of the original.

<img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9">
<img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3">
<img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7">
<img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19">
<img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8">
<img src="https://img.shields.io/badge/License-GPL--2.0--only-4C1?logo=gnu&logoColor=white" alt="GPL-2.0-only">

<img src="public/asset/legacy/image/scroll-lower.gif" alt="" width="273">

</div>

---

## Gameplay structure

<img src="public/asset/legacy/image/login-dragon.gif" alt="" align="right" width="180">

A player character is created as one of four races — troll, elf, human, or dwarf — and starts at the
village square. The character acquires equipment from the weapon shop and the armor shop, then
enters the forest.

Each encounter with a creature in the forest consumes one forest turn. Per encounter there is a
1-in-7 chance that a special event occurs instead of a creature, and on victory a 1-in-25 chance
that one additional gem is granted. Victory awards gold and experience, and if the character took
no hits in that fight, one or two forest turns are refunded. Defeat destroys all gold on hand,
reduces experience to 90% of its previous value, and puts the character into the slain state.

A character who has spent every forest turn buys ale at the inn or rents a room. When a new day
begins, hit points and forest turns are reset. The forest turn count above is adjusted by the day's
constitution, the mount, the previous day's drunkenness, and whether a spirit is attached. The
character raises levels to earn the recognition of the training masters, and ultimately faces the
green dragon at the castle.

### New day rules

| Target             | Processing                                                                                             |
| ------------------ | ------------------------------------------------------------------------------------------------------ |
| Hit points         | Restore to maximum hit points and clear the slain state                                                |
| Forest turns       | Add the day's constitution (−2 to +2) and the dragon point bonus fights to the base turn count         |
| Race adjustment    | Add 1 forest turn if the race is human                                                                 |
| Mount adjustment   | Add forest turns equal to the bonus fights of the owned mount                                          |
| Hangover           | Subtract 1 forest turn if drunkenness exceeds 66                                                       |
| Spirit adjustment  | Subtract 1 forest turn if a spirit is attached                                                         |
| Bank interest      | Add interest to the deposit if the previous day's remaining forest turns are at or below the threshold |
| Specialty uses     | Compute the daily use count of dark magic, mystical power, and thieving skill from proficiency         |
| Daily state values | Reset the player-versus-player fight count, outhouse usage, and room payment status                    |

## Regions and screens

<table>
<tr>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-village.gif" alt="" width="180"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-forest.gif" alt="" width="240"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-castle.gif" alt="" width="150"></td>
</tr>
<tr>
<td align="center"><b>Village square</b></td>
<td align="center"><b>Dark forest</b></td>
<td align="center"><b>Castle</b></td>
</tr>
</table>

The left navigation bar consists of four groups, and the screens in each group are as follows.

| Group  | Screens                                                                                                                                            |
| ------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Battle | Forest, warrior training, player versus player, green dragon, graveyard, bounty board                                                              |
| Shops  | Weapon shop, armor shop, bank, stables, gem trader, healer                                                                                         |
| Ville  | Village square, inn, gardens, veterans club, gypsy fortune teller, outhouse                                                                        |
| Other  | Daily news, message of the day, mail, warrior list, hall of fame, preferences, petition, game information, admin menu, weapon editor, armor editor |

### Special events

Sixteen special events replace a creature encounter. They fall into two kinds: those that grant a
reward immediately and end, and those that switch to a dedicated screen and require a choice.

| Event                                | Processing                                                                       |
| ------------------------------------ | -------------------------------------------------------------------------------- |
| Gem find · Gold find                 | Grants the reward immediately and ends                                           |
| Fair old man · Foul old man          | Grants the reward with no cost and ends                                          |
| Old man's wager · Old man to village | Takes the wager acceptance or the travel decision as a choice input              |
| Glowing stream · Fairy · Field       | Handles the interaction on a dedicated screen                                    |
| Riddle                               | Grants the reward on a correct answer, and ends with no reward on a wrong answer |
| Mad Audrey · Gold mine               | Choosing to take the risk raises the expected reward                             |
| Skill master                         | Judges the specialty proficiency and raises that proficiency                     |
| Shipwreck · Necromancer              | Accepting applies both a cost and a reward; declining leaves the state unchanged |
| Dark Horse Tavern                    | Switches to a dedicated screen and handles the tavern interaction                |

## Technology stack

| Area     | Stack                                                                                                                                                                                                                                                                                                                                                                                                       |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend  | <img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9"> <img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3"> <img src="https://img.shields.io/badge/Composer-885630?logo=composer&logoColor=white" alt="Composer">                                                                                                |
| Frontend | <img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7"> <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19"> <img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8"> <img src="https://img.shields.io/badge/Zod-4-3E67B1?logo=zod&logoColor=white" alt="Zod 4"> |
| Checks   | <img src="https://img.shields.io/badge/ESLint-10-4B32C3?logo=eslint&logoColor=white" alt="ESLint 10"> <img src="https://img.shields.io/badge/Prettier-3.9-F7B93E?logo=prettier&logoColor=black" alt="Prettier 3.9">                                                                                                                                                                                         |

- **Backend** — PHP 8.5.9, PSR-4 (`Lotdg\` → `api/src/`), SQLite 3 (PDO), Composer
- **Frontend** — TypeScript 7, React 19, Vite 8, Zod 4
- **Checks** — ESLint 10, Prettier 3.9, `tsc -b`, `php -l`
- **Localization** — Korean · English · Japanese · Chinese (Simplified) · Russian, using JSON labels and DB label tables in parallel
- **Presentation** — colors and dimensions measured from the legacy `yarbrough` template are fixed as tokens, and proportions hold up to 8K
- **License** — GPL-2.0-only ([LICENSE](LICENSE), [NOTICE.md](NOTICE.md), [AUTHORS.md](AUTHORS.md))

The legacy original is preserved under `reference/` and used only as comparison evidence. No code is
ported; everything is newly written.

## Directory structure

```
api/                            PHP backend
  bin/migrate.php               migration CLI
  bin/import-legacy-catalog.php catalog loader CLI based on the legacy dump
  config/application.php        single definition point for paths and environment
  database/
    migration/                  schema definitions
    seed/                       initial data for stat labels and admin accounts
    storage/                    SQLite file (git excluded)
  public/index.php              HTTP entry point
  src/
    Kernel/                     request lifecycle
    Http/                       route table, responses, controllers, middleware
    Persistence/                SQLite connection, migration, repositories
    Domain/                     domain logic (Account, Catalog, Social, World)
    I18n/                       localization
    Support/                    shared utilities

src/                            frontend
  app/                          root component, shell layout, screen code table
  feature/                      screens by domain (village, forest, battle, commerce, social, ...)
  shared/
    schema/                     Zod schemas (1:1 with the DB contract)
    constant/                   single definition point for color codes, locales, etc.
    type/  lib/  ui/
  i18n/locale/<code>/           per-language label JSON
  style/                        design tokens, legacy colors, layout

public/asset/legacy/image/      legacy image assets
reference/                      PHP4 original (for comparison, excluded from distribution)
```

## Database structure

The legacy build placed more than 100 columns in the single `accounts` table, mixing account data
and character data together. This reimplementation defines 40 tables decomposed by responsibility in
the single file `api/database/migration/0001_create_schema.sql`.

| Area      | Tables                                                                                                                                                                                                                              |
| --------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Account   | `account`, `account_privilege`, `account_device_fingerprint`, `account_preference`, `account_donation`, `account_referral`                                                                                                          |
| Character | `game_character`, `character_vital`, `character_combat_stat`, `character_progression`, `character_specialty`, `character_equipment`, `character_wealth`, `character_daily_allowance`, `character_social`, `character_session_state` |
| Catalog   | `weapon`, `armor`, `creature`, `training_master`, `mount`, `riddle`, `taunt`                                                                                                                                                        |
| Community | `daily_news`, `message_of_the_day`, `poll_result`, `commentary`, `mail_message`, `petition`                                                                                                                                         |
| Operation | `game_setting`, `access_ban`, `nasty_word`, `logdnet_server`, `referer_hit`, `login_failure_log`, `debug_log`                                                                                                                       |
| Locale    | `locale`, `label_key`, `label_translation`, `catalog_translation`                                                                                                                                                                   |

The fields the legacy build stored with `PHP serialize()` (`prefs`, `bufflist`, `dragonpoints`,
`donationconfig`, `mountbuff`, and so on) are all converted to JSON columns. Initial data loads the
stat labels and the two admin accounts from `database/seed/`.

## Localization structure

The legacy `translator.php` includes a per-language PHP file and then substitutes with `str_replace`
**using the source string itself as the key**. Under that scheme, a single character changed in the
source string prevents the translation from applying.

This reimplementation uses the `(namespace, label_path)` pair as the key, and the following two
paths share the same key scheme.

- Static resources — `src/i18n/locale/<code>/<namespace>.json`
  (`common`, `navigation`, `authentication`, `character-stat`, `village`, `forest`, `battle`,
  `commerce`, `social`, `system-message` — 10 in total)
- Dynamic lookup — the `label_key` and `label_translation` tables and `GET /api/locale/{locale_code}`

A key with no translation falls back to English (`en`). That rule matches the legacy behavior of
reverting to `translator_en.php` when `translator_<lang>.php` was absent.

## Presentation structure

`src/style/lotdg-design-token.css` holds the colors and dimensions measured from the legacy
`yarbrough` template as tokens. The layout does not reference the original pixel values directly; it
references only tokens derived from `--lotdg-scale-factor`, so proportions hold when the scale
changes.

| Viewport     | Scale |
| ------------ | ----- |
| ~1279px      | 1     |
| 1280px (HD)  | 1     |
| 1920px (FHD) | 1.5   |
| 2560px (QHD) | 2     |
| 3200px (3K)  | 2.5   |
| 3840px (4K)  | 3     |
| 5120px (5K)  | 4     |
| 6016px (6K)  | 4.5   |
| 7680px (8K)  | 6     |

When the viewport width is 720px or less, the left rail moves to the top of the body.

The legacy output color codes (`` `^ ``, `` `0 `` and the other 16 colors) are mapped to CSS classes
by `src/shared/constant/lotdg-legacy-color-code.ts` and implemented as tokens by
`src/style/lotdg-legacy-color-class.css`. Class names (`colDkBlue` and so on) are kept identical to
the legacy build.

### Image asset placement

The GIFs under `public/asset/legacy/image/` are the original assets, and each one is applied as
follows.

| Asset                                                                                                       | Applied to                                                      |
| ----------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| <img src="public/asset/legacy/image/title-banner.gif" width="150">                                          | Title banner in the shell header (`LotdgShellLayout`)           |
| <img src="public/asset/legacy/image/scroll-upper.gif" width="120">                                          | Upper cap of the left rail panel                                |
| <img src="public/asset/legacy/image/scroll-lower.gif" width="120">                                          | Lower cap of the left rail panel                                |
| <img src="public/asset/legacy/image/header-background.gif" width="150">                                     | Header background (`--lotdg-asset-header-background`)           |
| <img src="public/asset/legacy/image/footer-rule.gif" width="150">                                           | Footer rule (`--lotdg-asset-footer-rule`)                       |
| <img src="public/asset/legacy/image/login-dragon.gif" width="90">                                           | Dragon ring on the login screen (`--lotdg-asset-login-dragon`)  |
| <img src="public/asset/legacy/image/scene-village.gif" width="80">                                          | Village scene (`--lotdg-asset-scene-village`)                   |
| <img src="public/asset/legacy/image/scene-forest.gif" width="120">                                          | Forest scene (`--lotdg-asset-scene-forest`)                     |
| <img src="public/asset/legacy/image/scene-castle.gif" width="70">                                           | Castle scene (`--lotdg-asset-scene-castle`)                     |
| `marker-new.gif` · `scroll-new.gif` · `scroll-old.gif` · `signature-mightye.gif` · `spacer-transparent.gif` | Preserved original assets, not referenced by any current screen |

## Development

### Setup

```bash
npm install
cd api && composer install && cd ..
npm run migrate                          # api/bin/migrate.php — applies the SQLite schema
php api/bin/import-legacy-catalog.php    # loads weapons, armor, creatures and so on from the legacy dump
```

With no argument, the catalog loader CLI reads `reference/logd-0.9.7-create.sql` and prints the row
count loaded per table. To use a different dump, pass that file path as the argument.

### Running

`npm run dev` starts the frontend and the PHP backend together. The Vite dev server proxies `/api`
requests to the PHP built-in server at `127.0.0.1:8080`.

```bash
npm run dev           # web + api together (concurrently)
npm run dev:web       # vite only
npm run dev:api       # php -S 127.0.0.1:8080 -t api/public api/public/router.php
```

`api/public/router.php` is a fallback for the built-in server only. In an actual deployment the web
server's rewrite rules take over that role, so only `api/public/index.php` is designated as the
entry point.

### Building

```bash
npm run build         # build:web + build:api
npm run build:web     # tsc -b && vite build     → dist/
npm run build:api     # node scripts/build-api.mjs → dist/api/
```

`build:api` copies only `src` · `public` · `config` · `bin` · `database/migration` ·
`database/seed` · `composer.json` into `dist/api/`, then runs `composer install --no-dev
--optimize-autoloader` in that path. `reference/` and the SQLite file are excluded from the output.

### Checks

```bash
npm run check         # typecheck + lint + format:check
npm run typecheck     # tsc -b
npm run lint          # eslint
npm run lint:php      # composer run lint inside api (php -l over everything)
npm run format:check  # prettier
```

Verification is performed only through static analysis, type checking, and code review. Checking for
errors by starting a dev server or running a build is not used as a means of verification.

## License

GPL-2.0-only applies. The contributions of the original authors Eric Stevens and JT (Joe Naylor),
the legacy Korean localizers xc8oa and digirave, and the porter of this reimplementation
GarnetRapture are stated in [AUTHORS.md](AUTHORS.md). The document with legal effect is the English
original in [LICENSE](LICENSE); [NOTICE.md](NOTICE.md) is an informational notice in five languages.

<div align="center">

<img src="public/asset/legacy/image/footer-rule.gif" alt="" width="400">

Copyright 2002-2006 Eric Stevens &amp; JT · Ported by GarnetRapture · GPL-2.0-only

</div>
