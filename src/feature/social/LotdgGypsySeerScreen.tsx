import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgGypsyInspectSchema,
  lotdgGypsyListenSchema,
  type LotdgGypsyInspect,
} from '../../shared/schema/social/lotdg-social-response-schema'
import type { LotdgCommentaryEntry } from '../../shared/schema/social/lotdg-commentary-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgGypsySeerScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [seer, setSeer] = useState<LotdgGypsyInspect | null>(null)
  const [shadeList, setShadeList] = useState<readonly LotdgCommentaryEntry[] | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/gypsy/${characterId}/inspect`, lotdgGypsyInspectSchema)
      .then(setSeer)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const payAndListen = async () => {
    try {
      const result = await postForm(`/gypsy/${characterId}/listen`, lotdgGypsyListenSchema, {})

      if (!result.listened) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
        setShadeList(null)
      } else {
        setMessage(label('gypsy.paid', { cost: result.cost ?? 0 }))
        setShadeList(result.comment_list ?? [])
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('gypsy.title')}</h2>

      <p>{label('gypsy.description')}</p>

      {seer !== null && (
        <p>
          {label('gypsy.price', { cost: seer.cost, gold: seer.gold })}{' '}
          <button
            type="button"
            className="lotdg-button"
            disabled={!seer.affordable}
            onClick={() => void payAndListen()}
          >
            {label('gypsy.action.listen')}
          </button>
        </p>
      )}

      {shadeList !== null && (
        <>
          <h3>{label('gypsy.shade-title')}</h3>
          {shadeList.length === 0 ? (
            <p className="colDkWhite">{label('gypsy.shade-empty')}</p>
          ) : (
            shadeList.map((entry) => (
              <p key={entry.commentary_id}>
                <span className="colLtWhite">{entry.display_name}</span>{' '}
                {parseLegacyMarkup(entry.comment_text)}
              </p>
            ))
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
