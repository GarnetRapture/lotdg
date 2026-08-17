<div align="center">

<img src="public/asset/legacy/image/title-banner.gif" alt="Legend of the Green Dragon" width="395">

<img src="public/asset/legacy/image/scroll-upper.gif" alt="" width="273">

[한국어](README.md) · [English](README.en.md) · **日本語** · [简体中文](README.zh.md) · [Русский](README.ru.md)

PHP4 ベースのウェブテキスト RPG **Legend of the Green Dragon 0.9.7+jt** の韓国語版を<br>
原作の動作と同一に再実装したプロジェクトである。

<img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9">
<img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3">
<img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7">
<img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19">
<img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8">
<img src="https://img.shields.io/badge/License-GPL--2.0--only-4C1?logo=gnu&logoColor=white" alt="GPL-2.0-only">

<img src="public/asset/legacy/image/scroll-lower.gif" alt="" width="273">

</div>

---

## ゲーム進行の構成

<img src="public/asset/legacy/image/login-dragon.gif" alt="" align="right" width="180">

プレイヤーキャラクターはトロール、エルフ、人間、ドワーフのいずれかの種族として作成され、初期位置は
村の広場である。キャラクターは武器屋および防具屋で装備を取得した後、森へ進入する。

森でクリーチャーと遭遇するたびに森ターンが 1 減算される。遭遇 1 回につき 1/7 の確率でクリーチャーの
代わりに特殊事件が発生し、勝利時には 1/25 の確率で宝石 1 個が追加で支給される。勝利するとゴールドと
経験値が支給され、その戦闘で被弾がない場合は上記の森ターンが 1 または 2 返還される。敗北すると保有
ゴールドが全額消滅し、経験値は既存値の 90% に減少し、キャラクターは死亡状態へ移行する。

森ターンをすべて消費したキャラクターは宿屋でエールを購入するか部屋を借りる。新しい日が開始されると
体力と森ターンが再設定される。上記の森ターン数値は、その日の気力、騎乗動物、前日の酔い、怨霊の付着
有無に応じて加減される。キャラクターはレベルを上昇させて訓練所マスターの認定を受け、最終的に城で
緑の竜と対戦する。

### 日次更新規則

| 対象         | 処理内容                                                                     |
| ------------ | ---------------------------------------------------------------------------- |
| 体力         | 最大体力まで回復し、死亡状態を解除する                                       |
| 森ターン     | 基本ターン数にその日の気力（−2 〜 +2）とドラゴンポイントの追加戦闘数を加える |
| 種族補正     | 種族が人間である場合、森ターンを 1 加算する                                  |
| 騎乗補正     | 騎乗動物を保有する場合、当該騎乗動物の追加戦闘数だけ森ターンを加算する       |
| 二日酔い補正 | 酔いが 66 を超える場合、森ターンを 1 減算する                                |
| 怨霊補正     | 怨霊が付着している場合、森ターンを 1 減算する                                |
| 銀行利子     | 前日の森ターン残量が基準値以下である場合、預入金に利子を加算する             |
| 特技使用     | 闇の魔法、神秘の力、盗賊技術の日次使用回数を熟練度に応じて算定する           |
| 日次状態値   | プレイヤー対戦回数、便所の利用有無、部屋代の支払有無を初期化する             |

## 地域および画面の構成

<table>
<tr>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-village.gif" alt="" width="180"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-forest.gif" alt="" width="240"></td>
<td width="33%" align="center"><img src="public/asset/legacy/image/scene-castle.gif" alt="" width="150"></td>
</tr>
<tr>
<td align="center"><b>村の広場</b></td>
<td align="center"><b>暗い森</b></td>
<td align="center"><b>城</b></td>
</tr>
</table>

左側のナビゲーションバーは 4 つの束で構成され、各束に属する画面は次のとおりである。

| 束     | 画面                                                                                                                               |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------- |
| 戦闘   | 森、戦士訓練所、プレイヤー対戦、緑の竜、墓地、賞金掲示板                                                                           |
| 商店   | 武器屋、防具屋、銀行、厩舎、宝石商、治癒師                                                                                         |
| 村     | 村の広場、宿屋、庭園、ベテランクラブ、ジプシーの占い師、便所                                                                       |
| その他 | 今日の知らせ、告知、郵便、戦士名簿、名誉の殿堂、環境設定、運営への問い合わせ、ゲーム案内、管理メニュー、武器エディタ、防具エディタ |

### 特殊事件の構成

クリーチャー遭遇を代替する特殊事件は 16 種である。報酬を即時に支給して終了する類型と、専用画面へ
遷移して選択入力を要求する類型に区分される。

| 事件                        | 処理内容                                                   |
| --------------------------- | ---------------------------------------------------------- |
| 宝石拾い · 金貨拾い         | 報酬を即時に支給して終了する                               |
| 善い老人 · 悪い老人         | 対価なしに報酬を支給して終了する                           |
| 老人の賭け · 村へ向かう老人 | 賭けの受諾可否または移動可否を選択入力として受け取る       |
| 輝く小川 · 妖精 · 草原      | 専用画面で相互作用を処理する                               |
| 謎かけ                      | 正答時に報酬を支給し、誤答時は報酬なしで終了する           |
| 狂ったオードリー · 金鉱     | 危険を引き受けることを選択すると報酬の期待値が増加する     |
| スキルマスター              | 特技の熟練度を判定して熟練度を上昇させる                   |
| 遭難 · 降霊術師             | 受諾時は対価と報酬がともに適用され、拒否時は状態変更がない |
| ダークホース酒場            | 専用画面へ遷移して酒場の相互作用を処理する                 |

## 技術構成

| 区分           | スタック                                                                                                                                                                                                                                                                                                                                                                                                    |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| バックエンド   | <img src="https://img.shields.io/badge/PHP-8.5.9-777BB4?logo=php&logoColor=white" alt="PHP 8.5.9"> <img src="https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white" alt="SQLite 3"> <img src="https://img.shields.io/badge/Composer-885630?logo=composer&logoColor=white" alt="Composer">                                                                                                |
| フロントエンド | <img src="https://img.shields.io/badge/TypeScript-7-3178C6?logo=typescript&logoColor=white" alt="TypeScript 7"> <img src="https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black" alt="React 19"> <img src="https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white" alt="Vite 8"> <img src="https://img.shields.io/badge/Zod-4-3E67B1?logo=zod&logoColor=white" alt="Zod 4"> |
| 検査ツール     | <img src="https://img.shields.io/badge/ESLint-10-4B32C3?logo=eslint&logoColor=white" alt="ESLint 10"> <img src="https://img.shields.io/badge/Prettier-3.9-F7B93E?logo=prettier&logoColor=black" alt="Prettier 3.9">                                                                                                                                                                                         |

- **バックエンド** — PHP 8.5.9、PSR-4（`Lotdg\` → `api/src/`）、SQLite 3（PDO）、Composer
- **フロントエンド** — TypeScript 7、React 19、Vite 8、Zod 4
- **検査ツール** — ESLint 10、Prettier 3.9、`tsc -b`、`php -l`
- **多言語** — 韓国語 · 英語 · 日本語 · 中国語（簡体字）· ロシア語、JSON ラベルと DB ラベルテーブルを併用する
- **画面** — レガシーの `yarbrough` テンプレートで実測した色と寸法をトークンとして固定し、8K まで比率を維持する
- **ライセンス** — GPL-2.0-only（[LICENSE](LICENSE)、[NOTICE.md](NOTICE.md)、[AUTHORS.md](AUTHORS.md)）

レガシー原本は `reference/` に保存し、対照の根拠としてのみ使用する。コードは移植せず、すべて新規に
作成する。

## ディレクトリ構造

```
api/                            PHP バックエンド
  bin/migrate.php               マイグレーション適用 CLI
  bin/import-legacy-catalog.php レガシーダンプに基づくカタログ取り込み CLI
  config/application.php        パス・環境設定の単一定義箇所
  database/
    migration/                  スキーマ定義
    seed/                       ステータスラベル・管理者アカウントの初期データ
    storage/                    SQLite ファイル（git 除外）
  public/index.php              HTTP 入口
  src/
    Kernel/                     リクエストのライフサイクル
    Http/                       ルート表、レスポンス、コントローラ、ミドルウェア
    Persistence/                SQLite 接続・マイグレーション・リポジトリ
    Domain/                     ドメインロジック（Account, Catalog, Social, World）
    I18n/                       多言語処理
    Support/                    共通ユーティリティ

src/                            フロントエンド
  app/                          ルートコンポーネント、シェルレイアウト、画面コード表
  feature/                      ドメイン別画面（village, forest, battle, commerce, social, ...）
  shared/
    schema/                     Zod スキーマ（DB 契約と 1:1 対応）
    constant/                   カラーコード・ロケール等の単一定義箇所
    type/  lib/  ui/
  i18n/locale/<code>/           言語別ラベル JSON
  style/                        デザイントークン・レガシー色・レイアウト

public/asset/legacy/image/      レガシー画像資産
reference/                      PHP4 原本（対照用、配布除外）
```

## データベース構成

レガシーは `accounts` 単一テーブルに 100 個以上のカラムを配置し、アカウント情報とキャラクター情報を
混在させていた。本再実装は `api/database/migration/0001_create_schema.sql` 単一ファイルに責務別へ
分解した 40 個のテーブルを定義する。

| 領域         | テーブル                                                                                                                                                                                                                            |
| ------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| アカウント   | `account`, `account_privilege`, `account_device_fingerprint`, `account_preference`, `account_donation`, `account_referral`                                                                                                          |
| キャラクター | `game_character`, `character_vital`, `character_combat_stat`, `character_progression`, `character_specialty`, `character_equipment`, `character_wealth`, `character_daily_allowance`, `character_social`, `character_session_state` |
| カタログ     | `weapon`, `armor`, `creature`, `training_master`, `mount`, `riddle`, `taunt`                                                                                                                                                        |
| コミュニティ | `daily_news`, `message_of_the_day`, `poll_result`, `commentary`, `mail_message`, `petition`                                                                                                                                         |
| 運営         | `game_setting`, `access_ban`, `nasty_word`, `logdnet_server`, `referer_hit`, `login_failure_log`, `debug_log`                                                                                                                       |
| 多言語       | `locale`, `label_key`, `label_translation`, `catalog_translation`                                                                                                                                                                   |

レガシーが `PHP serialize()` で保存していたフィールド（`prefs`、`bufflist`、`dragonpoints`、
`donationconfig`、`mountbuff` 等）はすべて JSON カラムへ転換する。初期データは `database/seed/` の
ステータスラベルと管理者アカウント 2 件を取り込む。

## 多言語構成

レガシーの `translator.php` は言語別 PHP ファイルを include した後、**原文文字列そのものをキーとして
使用し** `str_replace` で置換する。上記の方式は原文が 1 文字でも変更されると翻訳が適用されない。

本再実装は `(namespace, label_path)` の組をキーとして使用し、同一のキー体系を次の 2 経路が共有する。

- 静的リソース — `src/i18n/locale/<code>/<namespace>.json`
  （`common`、`navigation`、`authentication`、`character-stat`、`village`、`forest`、`battle`、
  `commerce`、`social`、`system-message` の 10 種）
- 動的照会 — `label_key` · `label_translation` テーブルおよび `GET /api/locale/{locale_code}`

翻訳が存在しないキーはフォールバック言語である英語（`en`）で代替する。上記の規則は、レガシーが
`translator_<lang>.php` の不在時に `translator_en.php` へ復帰していた動作と同一である。

## 画面構成

`src/style/lotdg-design-token.css` は、レガシーの `yarbrough` テンプレートで実測した色と寸法を
トークンとして保管する。レイアウトは原本のピクセル値を直接参照せず、`--lotdg-scale-factor` から派生した
トークンのみを参照するため、倍率が変更されても比率が維持される。

| ビューポート | 倍率 |
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

ビューポート幅が 720px 以下である場合、左側のレールを本文上部へ移動させる。

レガシーの出力カラーコード（`` `^ ``、`` `0 `` 等 16 色）は `src/shared/constant/lotdg-legacy-color-code.ts`
が CSS クラスへマッピングし、`src/style/lotdg-legacy-color-class.css` がトークンとして実装する。
クラス名（`colDkBlue` 等）はレガシーと同一に維持する。

### 画像資産の配置

`public/asset/legacy/image/` の GIF は原本資産であり、各資産の適用位置は次のとおりである。

| 資産                                                                                                        | 適用位置                                             |
| ----------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| <img src="public/asset/legacy/image/title-banner.gif" width="150">                                          | シェル冒頭のタイトルバナー（`LotdgShellLayout`）     |
| <img src="public/asset/legacy/image/scroll-upper.gif" width="120">                                          | 左側レールパネル上端の端部                           |
| <img src="public/asset/legacy/image/scroll-lower.gif" width="120">                                          | 左側レールパネル下端の端部                           |
| <img src="public/asset/legacy/image/header-background.gif" width="150">                                     | ヘッダー背景（`--lotdg-asset-header-background`）    |
| <img src="public/asset/legacy/image/footer-rule.gif" width="150">                                           | フッター区切り線（`--lotdg-asset-footer-rule`）      |
| <img src="public/asset/legacy/image/login-dragon.gif" width="90">                                           | ログイン画面の竜の輪（`--lotdg-asset-login-dragon`） |
| <img src="public/asset/legacy/image/scene-village.gif" width="80">                                          | 村の場面（`--lotdg-asset-scene-village`）            |
| <img src="public/asset/legacy/image/scene-forest.gif" width="120">                                          | 森の場面（`--lotdg-asset-scene-forest`）             |
| <img src="public/asset/legacy/image/scene-castle.gif" width="70">                                           | 城の場面（`--lotdg-asset-scene-castle`）             |
| `marker-new.gif` · `scroll-new.gif` · `scroll-old.gif` · `signature-mightye.gif` · `spacer-transparent.gif` | 原本保存資産であり、現在の画面では参照しない         |

## 開発

### 準備

```bash
npm install
cd api && composer install && cd ..
npm run migrate                          # api/bin/migrate.php — SQLite スキーマ適用
php api/bin/import-legacy-catalog.php    # レガシーダンプから武器・防具・クリーチャー等を取り込む
```

カタログ取り込み CLI は引数を指定しない場合 `reference/logd-0.9.7-create.sql` を読み込み、取り込んだ
テーブル別の件数を出力する。他のダンプを使用する場合は当該ファイルパスを引数として指定する。

### 実行

`npm run dev` はフロントエンドと PHP バックエンドを同時に起動する。Vite 開発サーバーは `/api` 要求を
`127.0.0.1:8080` の PHP 組み込みサーバーへプロキシする。

```bash
npm run dev           # web + api 同時実行（concurrently）
npm run dev:web       # vite 単独
npm run dev:api       # php -S 127.0.0.1:8080 -t api/public api/public/router.php
```

`api/public/router.php` は組み込みサーバー専用のフォールバックである。実際の配備環境ではウェブ
サーバーの書き換え規則が上記の役割を代替するため、`api/public/index.php` のみを入口として指定する。

### ビルド

```bash
npm run build         # build:web + build:api
npm run build:web     # tsc -b && vite build     → dist/
npm run build:api     # node scripts/build-api.mjs → dist/api/
```

`build:api` は `src` · `public` · `config` · `bin` · `database/migration` · `database/seed` ·
`composer.json` のみを `dist/api/` へ複写した後、当該パスで `composer install --no-dev
--optimize-autoloader` を実行する。`reference/` と SQLite ファイルは成果物から除外する。

### 検査

```bash
npm run check         # typecheck + lint + format:check
npm run typecheck     # tsc -b
npm run lint          # eslint
npm run lint:php      # api 内で composer run lint（php -l 全体）
npm run format:check  # prettier
```

検証は静的解析、型検査、コードレビューのみで実施する。開発サーバーの起動またはビルド実行による
エラー確認は検証手段として使用しない。

## ライセンス

GPL-2.0-only を適用する。原著者 Eric Stevens および JT（Joe Naylor）、レガシー韓国語化の担当である
xc8oa および digirave、本再実装の移植者 GarnetRapture の寄与を [AUTHORS.md](AUTHORS.md) に明示する。
法的効力を持つ文書は [LICENSE](LICENSE) の英語原文であり、[NOTICE.md](NOTICE.md) は 5 言語の案内表示で
ある。

<div align="center">

<img src="public/asset/legacy/image/footer-rule.gif" alt="" width="400">

Copyright 2002-2006 Eric Stevens &amp; JT · Ported by GarnetRapture · GPL-2.0-only

</div>
