import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgGypsyInspectSchema,
  lotdgGypsyListenSchema,
  type LotdgGypsyInspect,
} from '../../shared/schema/social/lotdg-social-response-schema'
import type { LotdgCommentaryEntry } from '../../shared/schema/social/lotdg-commentary-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgCommentLine,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgText,
} from '../../shared/ui'

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
    <LotdgScreen titleText={label('gypsy.title')}>
      <LotdgText>{label('gypsy.description')}</LotdgText>

      {seer !== null && (
        <LotdgActionRow>
          <LotdgText>{label('gypsy.price', { cost: seer.cost, gold: seer.gold })}</LotdgText>
          <LotdgButton
            labelSlot={label('gypsy.action.listen')}
            isDisabled={!seer.affordable}
            onSelect={() => void payAndListen()}
          />
        </LotdgActionRow>
      )}

      {shadeList !== null && (
        <LotdgSection titleSlot={label('gypsy.shade-title')}>
          {shadeList.length === 0 ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {label('gypsy.shade-empty')}
            </LotdgText>
          ) : (
            shadeList.map((entry) => (
              <LotdgCommentLine
                key={entry.commentary_id}
                authorName={entry.display_name}
                commentText={entry.comment_text}
              />
            ))
          )}
        </LotdgSection>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
