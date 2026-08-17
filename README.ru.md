<div align="center">

<img src="public/asset/legacy/image/title-banner.gif" alt="Legend of the Green Dragon" width="395">

<img src="public/asset/legacy/image/scroll-upper.gif" alt="" width="273">

[한국어](README.md) · [English](README.en.md) · [日本語](README.ja.md) · [简体中文](README.zh.md) · **Русский**

Проект воспроизводит корейское издание веб-текстовой RPG **Legend of the Green Dragon 0.9.7+jt**<br>
на PHP4, повторяя поведение оригинала.

<img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9">
<img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3">
<img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7">
<img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19">
<img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8">
<img src="https://img.shields.io/badge/License-GPL--2.0--only-4C1?logo=gnu&logoColor=white" alt="GPL-2.0-only">

<img src="public/asset/legacy/image/scroll-lower.gif" alt="" width="273">

</div>

---

## Устройство игрового процесса

<img src="public/asset/legacy/image/login-dragon.gif" alt="" align="right" width="180">

Персонаж игрока создаётся с одной из рас — тролль, эльф, человек, гном, — а начальным местом
является деревенская площадь. Персонаж приобретает снаряжение в оружейной и в лавке доспехов, после
чего входит в лес.

При каждой встрече с существом в лесу лесной ход уменьшается на 1. На одну встречу приходится
вероятность 1/7, что вместо существа происходит особое событие, а при победе — вероятность 1/25, что
дополнительно выдаётся 1 самоцвет. Победа приносит золото и опыт, а если в этом бою не было
полученных ударов, указанный лесной ход возвращается в количестве 1 или 2. Поражение уничтожает всё
имеющееся золото, снижает опыт до 90% прежнего значения и переводит персонажа в состояние гибели.

Персонаж, израсходовавший все лесные ходы, покупает эль в таверне или снимает комнату. С началом
нового дня здоровье и лесные ходы сбрасываются. Указанное количество лесных ходов изменяется в
зависимости от выносливости дня, ездового животного, опьянения предыдущего дня и наличия
прикреплённого духа. Персонаж повышает уровни, получает признание мастеров тренировочного зала и в
конечном счёте сражается с зелёным драконом в замке.

### Правила обновления дня

| Объект                       | Обработка                                                                                                     |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Здоровье                     | Восстанавливается до максимума, состояние гибели снимается                                                    |
| Лесные ходы                  | К базовому числу ходов прибавляются выносливость дня (−2 … +2) и дополнительные бои за очки дракона           |
| Поправка на расу             | Если раса — человек, прибавляется 1 лесной ход                                                                |
| Поправка на ездовое животное | При наличии ездового животного прибавляется столько лесных ходов, сколько даёт его бонус боёв                 |
| Поправка на похмелье         | Если опьянение превышает 66, вычитается 1 лесной ход                                                          |
| Поправка на духа             | При прикреплённом духе вычитается 1 лесной ход                                                                |
| Банковский процент           | Если остаток лесных ходов предыдущего дня не превышает порога, к вкладу начисляется процент                   |
| Использование умений         | Дневное число применений тёмной магии, мистической силы и воровского навыка рассчитывается по уровню владения |
| Дневные состояния            | Сбрасываются число боёв между игроками, факт посещения нужника и факт оплаты комнаты                          |

## Устройство местностей и экранов

<table>
<tr>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-village.gif" alt="" width="180"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-forest.gif" alt="" width="240"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-castle.gif" alt="" width="150"></td>
</tr>
<tr>
<td align="center"><b>Деревенская площадь</b></td>
<td align="center"><b>Тёмный лес</b></td>
<td align="center"><b>Замок</b></td>
</tr>
</table>

Левая панель навигации состоит из 4 групп; экраны, входящие в каждую группу, приведены ниже.

| Группа  | Экраны                                                                                                                                                                   |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Бой     | Лес, тренировочный зал воинов, бой между игроками, зелёный дракон, кладбище, доска наград                                                                                |
| Лавки   | Оружейная, лавка доспехов, банк, конюшня, торговец самоцветами, целитель                                                                                                 |
| Деревня | Деревенская площадь, таверна, сады, клуб ветеранов, цыганская гадалка, нужник                                                                                            |
| Прочее  | Новости дня, объявление, почта, список воинов, зал славы, настройки, обращение к администрации, справка по игре, меню администратора, редактор оружия, редактор доспехов |

### Устройство особых событий

Особых событий, заменяющих встречу с существом, шестнадцать. Они делятся на те, что немедленно
выдают награду и завершаются, и те, что переключают на отдельный экран и требуют ввода выбора.

| Событие                                 | Обработка                                                                     |
| --------------------------------------- | ----------------------------------------------------------------------------- |
| Находка самоцвета · Находка золота      | Немедленно выдаёт награду и завершается                                       |
| Добрый старик · Злой старик             | Выдаёт награду без платы и завершается                                        |
| Пари старика · Старик, идущий в деревню | Принимает как ввод выбора согласие на пари либо решение о переходе            |
| Сияющий ручей · Фея · Луг               | Обрабатывает взаимодействие на отдельном экране                               |
| Загадка                                 | При верном ответе выдаёт награду, при неверном завершается без награды        |
| Безумная Одри · Золотой рудник          | Выбор пойти на риск повышает ожидаемую награду                                |
| Мастер навыков                          | Определяет уровень владения умением и повышает его                            |
| Кораблекрушение · Некромант             | При согласии применяются и плата, и награда; при отказе состояние не меняется |
| Таверна «Тёмная лошадка»                | Переключает на отдельный экран и обрабатывает взаимодействие в таверне        |

## Технологический состав

| Раздел               | Стек                                                                                                                                                                                                                                                                                                                                                                                                        |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Бэкенд               | <img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9"> <img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3"> <img src="https://img.shields.io/badge/Composer-885630?logo=composer&logoColor=white" alt="Composer">                                                                                                |
| Фронтенд             | <img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7"> <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19"> <img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8"> <img src="https://img.shields.io/badge/Zod-4-3E67B1?logo=zod&logoColor=white" alt="Zod 4"> |
| Инструменты проверки | <img src="https://img.shields.io/badge/ESLint-10-4B32C3?logo=eslint&logoColor=white" alt="ESLint 10"> <img src="https://img.shields.io/badge/Prettier-3.9-F7B93E?logo=prettier&logoColor=black" alt="Prettier 3.9">                                                                                                                                                                                         |

- **Бэкенд** — PHP 8.5.9, PSR-4 (`Lotdg\` → `api/src/`), SQLite 3 (PDO), Composer
- **Фронтенд** — TypeScript 7, React 19, Vite 8, Zod 4
- **Инструменты проверки** — ESLint 10, Prettier 3.9, `tsc -b`, `php -l`
- **Многоязычность** — корейский · английский · японский · китайский (упрощённый) · русский; JSON-метки и таблицы меток БД применяются параллельно
- **Экран** — цвета и размеры, измеренные по устаревшему шаблону `yarbrough`, зафиксированы в виде токенов, пропорции сохраняются вплоть до 8K
- **Лицензия** — GPL-2.0-only ([LICENSE](LICENSE), [NOTICE.md](NOTICE.md), [AUTHORS.md](AUTHORS.md))

Устаревший оригинал сохраняется в `reference/` и используется только как основание для сверки. Код
не переносится — всё написано заново.

## Структура каталогов

```
api/                            бэкенд на PHP
  bin/migrate.php               CLI применения миграций
  bin/import-legacy-catalog.php CLI загрузки каталога из устаревшего дампа
  config/application.php        единственное место определения путей и окружения
  database/
    migration/                  определения схемы
    seed/                       начальные данные меток характеристик и учётных записей администратора
    storage/                    файл SQLite (исключён из git)
  public/index.php              точка входа HTTP
  src/
    Kernel/                     жизненный цикл запроса
    Http/                       таблица маршрутов, ответы, контроллеры, посредники
    Persistence/                подключение к SQLite, миграции, репозитории
    Domain/                     доменная логика (Account, Catalog, Social, World)
    I18n/                       многоязычная обработка
    Support/                    общие утилиты

src/                            фронтенд
  app/                          корневой компонент, оболочка-макет, таблица кодов экранов
  feature/                      экраны по доменам (village, forest, battle, commerce, social, ...)
  shared/
    schema/                     схемы Zod (соответствие 1:1 контракту БД)
    constant/                   единственное место определения цветовых кодов, локалей и прочего
    type/  lib/  ui/
  i18n/locale/<code>/           JSON меток по языкам
  style/                        токены оформления, устаревшие цвета, макет

public/asset/legacy/image/      устаревшие графические ресурсы
reference/                      оригинал на PHP4 (для сверки, исключён из поставки)
```

## Устройство базы данных

Устаревшая версия размещала более 100 столбцов в единственной таблице `accounts`, смешивая данные
учётной записи и данные персонажа. Данная переработка определяет 40 таблиц, разложенных по зонам
ответственности, в единственном файле `api/database/migration/0001_create_schema.sql`.

| Область        | Таблицы                                                                                                                                                                                                                             |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Учётная запись | `account`, `account_privilege`, `account_device_fingerprint`, `account_preference`, `account_donation`, `account_referral`                                                                                                          |
| Персонаж       | `game_character`, `character_vital`, `character_combat_stat`, `character_progression`, `character_specialty`, `character_equipment`, `character_wealth`, `character_daily_allowance`, `character_social`, `character_session_state` |
| Каталог        | `weapon`, `armor`, `creature`, `training_master`, `mount`, `riddle`, `taunt`                                                                                                                                                        |
| Сообщество     | `daily_news`, `message_of_the_day`, `poll_result`, `commentary`, `mail_message`, `petition`                                                                                                                                         |
| Эксплуатация   | `game_setting`, `access_ban`, `nasty_word`, `logdnet_server`, `referer_hit`, `login_failure_log`, `debug_log`                                                                                                                       |
| Многоязычность | `locale`, `label_key`, `label_translation`, `catalog_translation`                                                                                                                                                                   |

Поля, которые устаревшая версия хранила через `PHP serialize()` (`prefs`, `bufflist`,
`dragonpoints`, `donationconfig`, `mountbuff` и другие), полностью переведены в столбцы JSON. В
качестве начальных данных загружаются метки характеристик и 2 учётные записи администратора из
`database/seed/`.

## Устройство многоязычности

Устаревший `translator.php` подключает PHP-файл нужного языка, а затем выполняет подстановку через
`str_replace`, **используя ключом саму исходную строку**. При таком способе изменение исходной строки
даже на один символ отменяет применение перевода.

Данная переработка использует ключом пару `(namespace, label_path)`, и эту же схему ключей разделяют
следующие два пути.

- Статические ресурсы — `src/i18n/locale/<code>/<namespace>.json`
  (`common`, `navigation`, `authentication`, `character-stat`, `village`, `forest`, `battle`,
  `commerce`, `social`, `system-message` — всего 10)
- Динамический поиск — таблицы `label_key` и `label_translation`, а также `GET /api/locale/{locale_code}`

Ключ, для которого перевода нет, заменяется резервным языком — английским (`en`). Указанное правило
совпадает с поведением устаревшей версии, которая при отсутствии `translator_<lang>.php`
возвращалась к `translator_en.php`.

## Устройство экрана

`src/style/lotdg-design-token.css` хранит в виде токенов цвета и размеры, измеренные по устаревшему
шаблону `yarbrough`. Макет не обращается к исходным значениям в пикселях напрямую, а использует
только токены, производные от `--lotdg-scale-factor`, поэтому при изменении масштаба пропорции
сохраняются.

| Область просмотра | Масштаб |
| ----------------- | ------- |
| ~1279px           | 1       |
| 1280px (HD)       | 1       |
| 1920px (FHD)      | 1.5     |
| 2560px (QHD)      | 2       |
| 3200px (3K)       | 2.5     |
| 3840px (4K)       | 3       |
| 5120px (5K)       | 4       |
| 6016px (6K)       | 4.5     |
| 7680px (8K)       | 6       |

Если ширина области просмотра составляет 720px или менее, левая панель перемещается наверх основного
содержимого.

Устаревшие коды цветов вывода (`` `^ ``, `` `0 `` и остальные 16 цветов) отображаются в CSS-классы
файлом `src/shared/constant/lotdg-legacy-color-code.ts` и реализуются токенами в
`src/style/lotdg-legacy-color-class.css`. Имена классов (`colDkBlue` и прочие) сохраняются такими же,
как в устаревшей версии.

### Размещение графических ресурсов

GIF-файлы в `public/asset/legacy/image/` являются оригинальными ресурсами; место применения каждого
из них приведено ниже.

| Ресурс                                                                                                      | Место применения                                                            |
| ----------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| <img src="public/asset/legacy/image/title-banner.gif" width="150">                                          | Баннер заголовка в шапке оболочки (`LotdgShellLayout`)                      |
| <img src="public/asset/legacy/image/scroll-upper.gif" width="120">                                          | Верхняя накладка панели левой полосы                                        |
| <img src="public/asset/legacy/image/scroll-lower.gif" width="120">                                          | Нижняя накладка панели левой полосы                                         |
| <img src="public/asset/legacy/image/header-background.gif" width="150">                                     | Фон шапки (`--lotdg-asset-header-background`)                               |
| <img src="public/asset/legacy/image/footer-rule.gif" width="150">                                           | Разделитель подвала (`--lotdg-asset-footer-rule`)                           |
| <img src="public/asset/legacy/image/login-dragon.gif" width="90">                                           | Кольцо дракона на экране входа (`--lotdg-asset-login-dragon`)               |
| <img src="public/asset/legacy/image/scene-village.gif" width="80">                                          | Сцена деревни (`--lotdg-asset-scene-village`)                               |
| <img src="public/asset/legacy/image/scene-forest.gif" width="120">                                          | Сцена леса (`--lotdg-asset-scene-forest`)                                   |
| <img src="public/asset/legacy/image/scene-castle.gif" width="70">                                           | Сцена замка (`--lotdg-asset-scene-castle`)                                  |
| `marker-new.gif` · `scroll-new.gif` · `scroll-old.gif` · `signature-mightye.gif` · `spacer-transparent.gif` | Сохранённые оригинальные ресурсы, ни один текущий экран к ним не обращается |

## Разработка

### Подготовка

```bash
npm install
cd api && composer install && cd ..
npm run migrate                          # api/bin/migrate.php — применение схемы SQLite
php api/bin/import-legacy-catalog.php    # загрузка оружия, доспехов, существ и прочего из устаревшего дампа
```

Без указания аргумента CLI загрузки каталога читает `reference/logd-0.9.7-create.sql` и выводит
число загруженных записей по каждой таблице. Для другого дампа путь к соответствующему файлу
указывается аргументом.

### Запуск

`npm run dev` одновременно поднимает фронтенд и бэкенд на PHP. Сервер разработки Vite проксирует
запросы `/api` на встроенный сервер PHP по адресу `127.0.0.1:8080`.

```bash
npm run dev           # web + api одновременно (concurrently)
npm run dev:web       # только vite
npm run dev:api       # php -S 127.0.0.1:8080 -t api/public api/public/router.php
```

`api/public/router.php` — запасной вариант исключительно для встроенного сервера. В реальной среде
развёртывания указанную роль берут на себя правила перезаписи веб-сервера, поэтому точкой входа
назначается только `api/public/index.php`.

### Сборка

```bash
npm run build         # build:web + build:api
npm run build:web     # tsc -b && vite build     → dist/
npm run build:api     # node scripts/build-api.mjs → dist/api/
```

`build:api` копирует в `dist/api/` только `src` · `public` · `config` · `bin` ·
`database/migration` · `database/seed` · `composer.json`, после чего выполняет по этому пути
`composer install --no-dev --optimize-autoloader`. `reference/` и файл SQLite исключаются из
результата сборки.

### Проверка

```bash
npm run check         # typecheck + lint + format:check
npm run typecheck     # tsc -b
npm run lint          # eslint
npm run lint:php      # composer run lint внутри api (php -l по всему коду)
npm run format:check  # prettier
```

Проверка выполняется только средствами статического анализа, проверки типов и обзора кода. Выявление
ошибок через запуск сервера разработки или выполнение сборки как средство проверки не применяется.

## Лицензия

Применяется GPL-2.0-only. Вклад первоначальных авторов Eric Stevens и JT (Joe Naylor), исполнителей
корейской локализации устаревшей сборки xc8oa и digirave, а также портировавшего данную переработку
GarnetRapture указан в [AUTHORS.md](AUTHORS.md). Юридическую силу имеет английский оригинал в
[LICENSE](LICENSE); [NOTICE.md](NOTICE.md) — информационное уведомление на 5 языках.

<div align="center">

<img src="public/asset/legacy/image/footer-rule.gif" alt="" width="400">

Copyright 2002-2006 Eric Stevens &amp; JT · Ported by GarnetRapture · GPL-2.0-only

</div>
