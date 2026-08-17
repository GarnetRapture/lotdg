import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgNavigationGroupProps } from '../type/lotdg-ui-component-contract'

export function LotdgNavigationGroup({ headText, children }: LotdgNavigationGroupProps) {
  return (
    <div>
      <span className={LOTDG_UI_CLASS_NAME.NAVIGATION_HEAD}>{headText}</span>
      {children}
    </div>
  )
}
