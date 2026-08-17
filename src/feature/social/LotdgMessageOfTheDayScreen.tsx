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
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'

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
    <section>
      <h2>{label('motd.title')}</h2>

      {notice !== null && (
        <>
          {notice.has_unseen && (
            <p>
              <button type="button" className="lotdg-button" onClick={() => void markSeen()}>
                {label('motd.action.mark-seen')}
              </button>
            </p>
          )}

          {notice.notice_list.length === 0 ? (
            <p className="colDkWhite">{label('motd.empty')}</p>
          ) : (
            notice.notice_list.map((entry) => (
              <article key={entry.motd_id}>
                <h3>
                  {parseLegacyMarkup(entry.title)}
                  {entry.is_unseen && <span className="colLtGreen"> {label('motd.unseen')}</span>}
                </h3>
                <p className="colDkWhite">{entry.posted_at}</p>
                <p>{parseLegacyMarkup(entry.body)}</p>

                {entry.motd_type === LOTDG_MOTD_TYPE.POLL && entry.poll_result !== null && (
                  <p>
                    {entry.choice_list.map((choiceText, choiceIndex) => (
                      <button
                        key={choiceText}
                        type="button"
                        className="lotdg-button"
                        disabled={entry.poll_result?.own_choice === choiceIndex}
                        onClick={() => void vote(entry.motd_id, choiceIndex)}
                      >
                        {parseLegacyMarkup(choiceText)} (
                        {entry.poll_result?.count_by_choice[String(choiceIndex)] ?? 0})
                      </button>
                    ))}{' '}
                    {label('motd.total-vote', { total: entry.poll_result.total_vote })}
                  </p>
                )}
              </article>
            ))
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
