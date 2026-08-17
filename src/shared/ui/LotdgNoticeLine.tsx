import { parseLegacyMarkup } from '../lib/lotdg-legacy-markup-parser'
import { LOTDG_NOTICE_TONE } from '../constant/lotdg-notice-tone'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import { joinClassName } from '../lib/lotdg-class-name-joiner'
import type { LotdgNoticeLineProps } from '../type/lotdg-ui-component-contract'

export function LotdgNoticeLine({
  messageText,
  tone = LOTDG_NOTICE_TONE.INFORMATION,
}: LotdgNoticeLineProps) {
  if (messageText === '') {
    return null
  }

  return (
    <p className={joinClassName(LOTDG_UI_CLASS_NAME.NOTICE_LINE, tone)}>
      {parseLegacyMarkup(messageText)}
    </p>
  )
}
