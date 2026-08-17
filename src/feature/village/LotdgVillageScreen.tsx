import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgVillageResponseSchema,
  type LotdgVillageResponse,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveNewsText } from '../../shared/lib/lotdg-news-label-resolver'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_COMMENTARY_SECTION_CODE } from '../../shared/constant/lotdg-commentary-section-code'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgMarkupText, LotdgScreen, LotdgSection, LotdgText } from '../../shared/ui'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgVillageScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [village, setVillage] = useState<LotdgVillageResponse | null>(null)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    let isMounted = true

    getJson(`/village/${characterId}`, lotdgVillageResponseSchema)
      .then((result) => {
        if (isMounted) {
          setVillage(result)
        }
      })
      .catch((error: unknown) => {
        if (isMounted) {
          setErrorMessage(resolveErrorLabel(error, translate))
        }
      })

    return () => {
      isMounted = false
    }
  }, [characterId, translate])

  if (errorMessage !== '') {
    return (
      <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>{errorMessage}</LotdgText>
    )
  }

  if (village === null) {
    return null
  }

  if (!village.entered) {
    return (
      <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
        {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'error.character-dead')}
      </LotdgText>
    )
  }

  return (
    <LotdgScreen titleText={translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'title')}>
      <LotdgText>{translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'description')}</LotdgText>

      {village.latest_news !== null && village.latest_news !== undefined && (
        <LotdgSection titleSlot={translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'latest-news')}>
          <LotdgMarkupText sourceText={resolveNewsText(village.latest_news.news_text, translate)} />
        </LotdgSection>
      )}

      <LotdgText>
        {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'clock', {
          time: village.game_time ?? '',
        })}
      </LotdgText>

      {village.auto_master_challenge?.triggered === true && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
          {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'auto-master.truant')}
        </LotdgText>
      )}

      <LotdgCommentaryBoard
        characterId={characterId}
        sectionCode={LOTDG_COMMENTARY_SECTION_CODE.VILLAGE}
      />
    </LotdgScreen>
  )
}
