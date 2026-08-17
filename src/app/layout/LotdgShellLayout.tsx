import { LOTDG_LEGACY_ASSET_SOURCE } from '../../shared/constant/lotdg-legacy-asset-source'
import {
  LOTDG_PANEL_VARIANT_CODE,
  LOTDG_STAGE_CLASS_NAME,
  LOTDG_UI_CLASS_NAME,
} from '../../shared/constant/lotdg-ui-class-name'
import type { LotdgShellLayoutProps } from '../../shared/type/lotdg-ui-component-contract'
import { LotdgScrollPanel } from '../../shared/ui/LotdgScrollPanel'

export function LotdgShellLayout({
  pageTitle,
  bannerAlternativeText,
  headerLinkSlot,
  navigationSlot,
  preferenceSlot,
  characterStatSlot,
  stageSlot,
  stageSceneCode,
  footerSlot,
}: LotdgShellLayoutProps) {
  return (
    <div className={LOTDG_UI_CLASS_NAME.SHELL_ROOT}>
      <header className={LOTDG_UI_CLASS_NAME.SHELL_HEADER}>
        <img
          className={LOTDG_UI_CLASS_NAME.SHELL_TITLE_BANNER}
          src={LOTDG_LEGACY_ASSET_SOURCE.TITLE_BANNER}
          alt={bannerAlternativeText}
        />
        <h1 className={LOTDG_UI_CLASS_NAME.SHELL_PAGE_TITLE}>{pageTitle}</h1>
        <p className={LOTDG_UI_CLASS_NAME.SHELL_HEADER_LINK}>{headerLinkSlot}</p>
      </header>

      <nav className={LOTDG_UI_CLASS_NAME.SHELL_RAIL}>
        <LotdgScrollPanel variantCode={LOTDG_PANEL_VARIANT_CODE.NAVIGATION}>
          {navigationSlot}
        </LotdgScrollPanel>
        <LotdgScrollPanel variantCode={LOTDG_PANEL_VARIANT_CODE.LOCALE}>
          {preferenceSlot}
        </LotdgScrollPanel>
      </nav>

      <main className={LOTDG_STAGE_CLASS_NAME[stageSceneCode]}>
        <aside className={LOTDG_UI_CLASS_NAME.SHELL_STAT}>
          <LotdgScrollPanel variantCode={LOTDG_PANEL_VARIANT_CODE.VITAL_INFO}>
            {characterStatSlot}
          </LotdgScrollPanel>
        </aside>

        {stageSlot}
      </main>

      <footer className={LOTDG_UI_CLASS_NAME.SHELL_FOOTER}>{footerSlot}</footer>
    </div>
  )
}
