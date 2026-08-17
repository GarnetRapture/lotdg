import type { ReactNode } from 'react'

const LOTDG_SCROLL_CAP_UPPER_SOURCE = '/asset/legacy/image/scroll-upper.gif'
const LOTDG_SCROLL_CAP_LOWER_SOURCE = '/asset/legacy/image/scroll-lower.gif'
const LOTDG_TITLE_BANNER_SOURCE = '/asset/legacy/image/title-banner.gif'

interface LotdgShellLayoutProps {
  readonly pageTitle: string
  readonly navigationSlot: ReactNode
  readonly characterStatSlot: ReactNode
  readonly stageSlot: ReactNode
  readonly footerSlot: ReactNode
}

function LotdgRailPanel({
  bodyModifierClassName,
  children,
}: {
  readonly bodyModifierClassName: string
  readonly children: ReactNode
}) {
  return (
    <div className="lotdg-panel">
      <img className="lotdg-panel__cap" src={LOTDG_SCROLL_CAP_UPPER_SOURCE} alt="" />
      <div className={`lotdg-panel__body ${bodyModifierClassName}`}>{children}</div>
      <img className="lotdg-panel__cap" src={LOTDG_SCROLL_CAP_LOWER_SOURCE} alt="" />
    </div>
  )
}

export function LotdgShellLayout({
  pageTitle,
  navigationSlot,
  characterStatSlot,
  stageSlot,
  footerSlot,
}: LotdgShellLayoutProps) {
  return (
    <div className="lotdg-shell">
      <header className="lotdg-shell__header">
        <h1 className="lotdg-shell__page-title">{pageTitle}</h1>
        <img
          className="lotdg-shell__title-banner"
          src={LOTDG_TITLE_BANNER_SOURCE}
          alt="Legend of the Green Dragon"
        />
      </header>

      <nav className="lotdg-shell__rail">
        <LotdgRailPanel bodyModifierClassName="lotdg-panel__body--navigation">
          {navigationSlot}
        </LotdgRailPanel>
        <LotdgRailPanel bodyModifierClassName="lotdg-panel__body--vital-info">
          {characterStatSlot}
        </LotdgRailPanel>
      </nav>

      <main className="lotdg-shell__stage">{stageSlot}</main>

      <footer className="lotdg-shell__footer">{footerSlot}</footer>
    </div>
  )
}
