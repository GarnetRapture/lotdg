import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgMessageOfTheDayListSchema,
  lotdgMessageOfTheDaySeenSchema,
  lotdgMessageOfTheDayVoteSchema,
  type LotdgMessageOfTheDayList,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_MOTD_TYPE } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgInlineText,
  LotdgMarkupText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgText,
} from '../../shared/ui'

export function LotdgMessageOfTheDayScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [notice, setNotice] = useState<LotdgMessageOfTheDayList | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/motd/${characterId}/list`, lotdgMessageOfTheDayListSchema)
      .then(setNotice)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const markSeen = async () => {
    try {
      await postForm(`/motd/${characterId}/seen`, lotdgMessageOfTheDaySeenSchema, {})
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const vote = async (motdId: number, choiceIndex: number) => {
    try {
      const result = await postForm(`/motd/${characterId}/vote`, lotdgMessageOfTheDayVoteSchema, {
        motd_id: motdId,
        choice_index: choiceIndex,
      })

      setMessage(
        result.voted ? label('motd.voted') : resolveMessageKeyLabel(result.message_key, translate),
      )

      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('motd.title')}>
      {notice !== null && (
        <>
          {notice.has_unseen && (
            <LotdgActionRow>
              <LotdgButton
                labelSlot={label('motd.action.mark-seen')}
                onSelect={() => void markSeen()}
              />
            </LotdgActionRow>
          )}

          {notice.notice_list.length === 0 ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {label('motd.empty')}
            </LotdgText>
          ) : (
            notice.notice_list.map((entry) => (
              <LotdgSection
                key={entry.motd_id}
                titleSlot={
                  <>
                    {parseLegacyMarkup(entry.title)}
                    {entry.is_unseen && (
                      <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
                        {' '}
                        {label('motd.unseen')}
                      </LotdgInlineText>
                    )}
                  </>
                }
              >
                <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
                  {entry.posted_at}
                </LotdgText>
                <LotdgMarkupText sourceText={entry.body} />

                {entry.motd_type === LOTDG_MOTD_TYPE.POLL && entry.poll_result !== null && (
                  <LotdgActionRow>
                    {entry.choice_list.map((choiceText, choiceIndex) => (
                      <LotdgButton
                        key={choiceText}
                        labelSlot={
                          <>
                            {parseLegacyMarkup(choiceText)} (
                            {entry.poll_result?.count_by_choice[String(choiceIndex)] ?? 0})
                          </>
                        }
                        isDisabled={entry.poll_result?.own_choice === choiceIndex}
                        onSelect={() => void vote(entry.motd_id, choiceIndex)}
                      />
                    ))}
                    <LotdgText>
                      {label('motd.total-vote', { total: entry.poll_result.total_vote })}
                    </LotdgText>
                  </LotdgActionRow>
                )}
              </LotdgSection>
            ))
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
