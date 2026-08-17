# Authors and Contributors

This project is a reimplementation of **Legend of the Green Dragon** (LoGD), originally written in
PHP4. The upstream work is licensed under the **GNU General Public License, version 2** — see
[LICENSE](LICENSE) for the verbatim license text, and [NOTICE.md](NOTICE.md) for the copyright
notice in five languages.

Everyone listed below holds credit in the work that this repository builds upon. Attribution is
retained under GPL-2.0 §2(a) and §1.

## Upstream authors (Legend of the Green Dragon)

- **Eric Stevens** — original author of Legend of the Green Dragon.
  Evidence: `reference/common.php` footer string, "Copyright 2002-2006 … Eric Stevens".
- **JT (Joe Naylor)** — contributor; the version tag is `0.9.7+jt`.
  Evidence: `reference/common.php` `$logd_version = "0.9.7+jt"`, and the riddle import attribution
  in `reference/logd-0.9.7-create.sql`.
- **Chris Yarbrough** — author of the default `yarbrough` template.
  Evidence: `reference/templates/yarbrough.htm` footer credit.
- **MightyE** — contributor credited throughout the source and the game content.
  Evidence: `reference/translator.php` docblock; in-game item and shop naming.
- **Mark Manning** — compiler of the NetBook of Riddles used to populate `riddles`.
  Evidence: riddle import comment in `reference/logd-0.9.7-create.sql`.

### Content contributors credited in the original game data

Named in `reference/logd-0.9.7-create.sql` as `creatures.createdby` / `taunts.editor`:
**Appleshiner**, **foilwench**, **Bluspring**, **Hank**, **Moonchilde**, **Joe**.

## Korean localization of the legacy build

- **xc8oa** — Korean translation and adaptation.
  Evidence: `reference/common.php` footer, "번역/제작: xc8oa, digirave".
- **digirave** — Korean translation and adaptation.
  Evidence: the same footer line.

The legacy Korean build was distributed via `gagax.com`, credited in the same footer string.

## This reimplementation

- **GarnetRapture** &lt;taeyeon9_3@naver.com&gt; — porter and maintainer.
  PHP 8.5 / SQLite3 / TypeScript reimplementation, five-language label normalization, and the
  open-source release of this repository.

## Scope of this reimplementation

- The backend is rewritten from scratch against **PHP 8.5.9** using **PSR-4** autoloading
  (`Lotdg\` → `api/src/`) and **SQLite 3** via PDO. It is not a copy of the PHP4 sources.
- The frontend is written in **TypeScript** and **React** with **Zod** schemas mirroring the
  database contract.
- Game data, message text, and visual design are derived from the original work and therefore
  remain under GPL-2.0. Design tokens in `src/style/lotdg-design-token.css` reproduce the measured
  palette and dimensions of the original `yarbrough` template.
- The original PHP4 sources are kept under `reference/` for forensic comparison and are excluded
  from distribution builds.
