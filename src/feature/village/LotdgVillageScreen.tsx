import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgVillageResponseSchema,
  type LotdgVillageResponse,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveNewsText } from '../../shared/lib/lotdg-news-label-resolver'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgVillageScreen({ characterId }: { readonly characterId: number }) {
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
    return <p className="colLtRed">{errorMessage}</p>
  }

  if (village === null) {
    return null
  }

  if (!village.entered) {
    return (
      <p className="colLtRed">
        {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'error.character-dead')}
      </p>
    )
  }

  return (
    <section>
      <h2>{translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'title')}</h2>

      <p>{translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'description')}</p>

      {village.latest_news !== null && village.latest_news !== undefined && (
        <p>
          <b>{translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'latest-news')}</b>
          <br />
          {parseLegacyMarkup(resolveNewsText(village.latest_news.news_text, translate))}
        </p>
      )}

      <p>
        {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'clock', {
          time: village.game_time ?? '',
        })}
      </p>

      {village.auto_master_challenge?.triggered === true && (
        <p className="colLtYellow">
          {translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, 'auto-master.truant')}
        </p>
      )}

      <LotdgCommentaryBoard characterId={characterId} sectionCode="village" />
    </section>
  )
}
