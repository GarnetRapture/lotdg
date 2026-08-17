import { parseLegacyMarkup } from '../lib/lotdg-legacy-markup-parser'
import type { LotdgMarkupTextProps } from '../type/lotdg-ui-component-contract'
import { LotdgText } from './LotdgText'

export function LotdgMarkupText({
  sourceText,
  weaponName = '',
  colorClassName,
  isCentered = false,
}: LotdgMarkupTextProps) {
  if (sourceText === '') {
    return null
  }

  return (
    <LotdgText colorClassName={colorClassName} isCentered={isCentered}>
      {parseLegacyMarkup(sourceText, weaponName)}
    </LotdgText>
  )
}
