import type { ReactNode } from 'react'
import type { LotdgStageSceneCode } from './lotdg-stage-scene-code'

const LOTDG_SCROLL_CAP_UPPER_SOURCE = '/asset/legacy/image/scroll-upper.gif'
const LOTDG_SCROLL_CAP_LOWER_SOURCE = '/asset/legacy/image/scroll-lower.gif'
const LOTDG_TITLE_BANNER_SOURCE = '/asset/legacy/image/title-banner.gif'

interface LotdgShellLayoutProps {
  readonly pageTitle: string
  readonly headerLinkSlot: ReactNode
  readonly navigationSlot: ReactNode
  readonly localeSlot: ReactNode
  readonly characterStatSlot: ReactNode
  readonly stageSlot: ReactNode
  readonly stageSceneCode: LotdgStageSceneCode
  readonly footerSlot: ReactNode
}

function LotdgScrollPanel({
  modifierClassName,
  children,
}: {
  readonly modifierClassName: string
  readonly children: ReactNode
}) {
  return (
    <div className={`lotdg-panel lotdg-panel--${modifierClassName}`}>
      <img className="lotdg-panel__cap" src={LOTDG_SCROLL_CAP_UPPER_SOURCE} alt="" />
      <div className={`lotdg-panel__body lotdg-panel__body--${modifierClassName}`}>
        {children}
      </div>
      <img className="lotdg-panel__cap" src={LOTDG_SCROLL_CAP_LOWER_SOURCE} alt="" />
    </div>
  )
}

export function LotdgShellLayout({
  pageTitle,
  headerLinkSlot,
  navigationSlot,
  localeSlot,
  characterStatSlot,
  stageSlot,
  stageSceneCode,
  footerSlot,
}: LotdgShellLayoutProps) {
  return (
    <div className="lotdg-shell">
      <header className="lotdg-shell__header">
        <img
          className="lotdg-shell__title-banner"
          src={LOTDG_TITLE_BANNER_SOURCE}
          alt="Legend of the Green Dragon"
        />
        <h1 className="lotdg-shell__page-title">{pageTitle}</h1>
        <p className="lotdg-shell__header-link">{headerLinkSlot}</p>
      </header>

      <nav className="lotdg-shell__rail">
        <LotdgScrollPanel modifierClassName="navigation">{navigationSlot}</LotdgScrollPanel>
        <LotdgScrollPanel modifierClassName="locale">{localeSlot}</LotdgScrollPanel>
      </nav>

      <main className={`lotdg-shell__stage lotdg-shell__stage--scene-${stageSceneCode}`}>
        <aside className="lotdg-shell__stat">
          <LotdgScrollPanel modifierClassName="vital-info">{characterStatSlot}</LotdgScrollPanel>
        </aside>

        {stageSlot}
      </main>

      <footer className="lotdg-shell__footer">{footerSlot}</footer>
    </div>
  )
}
