import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgActionRowProps } from '../type/lotdg-ui-component-contract'

export function LotdgActionRow({ children, isCentered = false }: LotdgActionRowProps) {
  return (
    <div
      className={
        isCentered ? LOTDG_UI_CLASS_NAME.ACTION_ROW_CENTERED : LOTDG_UI_CLASS_NAME.ACTION_ROW
      }
    >
      {children}
    </div>
  )
}
