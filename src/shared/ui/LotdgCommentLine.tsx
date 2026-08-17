import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../constant/lotdg-legacy-color-code'
import { parseLegacyMarkup } from '../lib/lotdg-legacy-markup-parser'
import type { LotdgCommentLineProps } from '../type/lotdg-ui-component-contract'
import { LotdgInlineText } from './LotdgInlineText'
import { LotdgText } from './LotdgText'

export function LotdgCommentLine({ authorName, commentText }: LotdgCommentLineProps) {
  return (
    <LotdgText>
      <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_WHITE}>
        {authorName}
      </LotdgInlineText>{' '}
      {parseLegacyMarkup(commentText)}
    </LotdgText>
  )
}
