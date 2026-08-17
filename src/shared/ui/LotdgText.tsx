import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import { joinClassName } from '../lib/lotdg-class-name-joiner'
import type { LotdgTextProps } from '../type/lotdg-ui-component-contract'

export function LotdgText({ children, colorClassName, isCentered = false }: LotdgTextProps) {
  return (
    <p
      className={joinClassName(
        LOTDG_UI_CLASS_NAME.TEXT,
        colorClassName,
        isCentered ? LOTDG_UI_CLASS_NAME.ALIGN_CENTER : undefined,
      )}
    >
      {children}
    </p>
  )
}
