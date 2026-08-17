import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgSectionProps } from '../type/lotdg-ui-component-contract'

export function LotdgSection({ titleSlot, children }: LotdgSectionProps) {
  return (
    <section className={LOTDG_UI_CLASS_NAME.SECTION_ROOT}>
      {titleSlot !== undefined && (
        <h3 className={LOTDG_UI_CLASS_NAME.SECTION_TITLE}>{titleSlot}</h3>
      )}
      <div className={LOTDG_UI_CLASS_NAME.SECTION_BODY}>{children}</div>
    </section>
  )
}
