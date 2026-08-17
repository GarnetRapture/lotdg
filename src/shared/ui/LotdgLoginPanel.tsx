import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import { joinClassName } from '../lib/lotdg-class-name-joiner'
import type { LotdgChildrenProps } from '../type/lotdg-ui-component-contract'

export function LotdgLoginPanel({ children }: LotdgChildrenProps) {
  return (
    <div className={LOTDG_UI_CLASS_NAME.ALIGN_CENTER}>
      <div className={joinClassName(LOTDG_UI_CLASS_NAME.LOGIN_PANEL)}>{children}</div>
    </div>
  )
}
