import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgNoticeLinkProps } from '../type/lotdg-ui-component-contract'

export function LotdgNoticeLink({ hashCode, labelText, onSelect }: LotdgNoticeLinkProps) {
  return (
    <a
      className={LOTDG_UI_CLASS_NAME.NOTICE_LINK}
      href={`#${hashCode}`}
      onClick={(event) => {
        event.preventDefault()
        onSelect()
      }}
    >
      {labelText}
    </a>
  )
}
