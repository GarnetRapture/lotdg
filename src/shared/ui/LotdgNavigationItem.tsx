import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgNavigationItemProps } from '../type/lotdg-ui-component-contract'

export function LotdgNavigationItem({
  hashCode,
  labelText,
  onSelect,
  isCurrent = false,
}: LotdgNavigationItemProps) {
  return (
    <a
      className={LOTDG_UI_CLASS_NAME.NAVIGATION_ITEM}
      href={`#${hashCode}`}
      aria-current={isCurrent}
      onClick={(event) => {
        event.preventDefault()
        onSelect()
      }}
    >
      {labelText}
    </a>
  )
}
