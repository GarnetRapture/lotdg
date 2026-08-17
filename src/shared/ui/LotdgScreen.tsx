import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgScreenProps } from '../type/lotdg-ui-component-contract'

export function LotdgScreen({ titleText, children }: LotdgScreenProps) {
  return (
    <section className={LOTDG_UI_CLASS_NAME.SCREEN_ROOT}>
      <h2 className={LOTDG_UI_CLASS_NAME.SCREEN_TITLE}>{titleText}</h2>
      {children}
    </section>
  )
}
