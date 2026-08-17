import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgFieldRowProps } from '../type/lotdg-ui-component-contract'

export function LotdgFieldRow({ children, isStacked = false }: LotdgFieldRowProps) {
  return (
    <div
      className={
        isStacked ? LOTDG_UI_CLASS_NAME.FIELD_ROW_STACKED : LOTDG_UI_CLASS_NAME.FIELD_ROW
      }
    >
      {children}
    </div>
  )
}
