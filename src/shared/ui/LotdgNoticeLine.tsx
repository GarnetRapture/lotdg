import { parseLegacyMarkup } from '../lib/lotdg-legacy-markup-parser'
import { LOTDG_NOTICE_TONE, type LotdgNoticeTone } from '../constant/lotdg-notice-tone'

export function LotdgNoticeLine({
  messageText,
  tone = LOTDG_NOTICE_TONE.INFORMATION,
}: {
  readonly messageText: string
  readonly tone?: LotdgNoticeTone
}) {
  if (messageText === '') {
    return null
  }

  return <p className={tone}>{parseLegacyMarkup(messageText)}</p>
}
