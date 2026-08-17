import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgGameInformationSchema,
  lotdgWebVoteClaimSchema,
  lotdgWebVoteSchema,
  type LotdgGameInformation,
  type LotdgWebVote,
} from '../../shared/schema/catalog/lotdg-editor-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgGameInformationScreen({
  characterId,
  onStateChange,
}: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [information, setInformation] = useState<LotdgGameInformation | null>(null)
  const [webVote, setWebVote] = useState<LotdgWebVote | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson('/game-information', lotdgGameInformationSchema)
      .then(setInformation)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })

    getJson(`/web-vote/${characterId}/inspect`, lotdgWebVoteSchema)
      .then(setWebVote)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMON, path, valueMap)

  const claim = async () => {
    try {
      const result = await postForm(
        `/web-vote/${characterId}/claim`,
        lotdgWebVoteClaimSchema,
        {},
      )

      setMessage(
        result.claimed
          ? label('game-information.vote.claimed', { gem: result.gem_gained ?? 0 })
          : resolveMessageKeyLabel(result.message_key, translate),
      )

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('game-information.title')}</h2>

      {information !== null && (
        <>
          <p>
            {label('game-information.credit', {
              author: information.original_author,
              porter: information.porter_name,
              license: information.license_code,
            })}
          </p>

          <p>
            {label('game-information.clock', {
              daysPerDay: information.days_per_calendar_day,
              hour: information.day_duration_hour,
              gameTime: information.game_time,
              gameDate: information.game_date,
              serverTime: information.server_time,
              second: information.real_seconds_until_next_game_day,
            })}
          </p>

          {Object.entries(information.setting_group_map).map(([groupCode, entryList]) => (
            <section key={groupCode}>
              <h3>{label(`game-information.group.${groupCode}`)}</h3>

              <LotdgDataTable
                rowList={entryList}
                rowKey={(entry) => entry.setting_key}
                columnList={[
                  {
                    columnKey: 'key',
                    headText: label('game-information.column.setting'),
                    render: (entry) => label(`game-information.setting.${entry.setting_key}`),
                  },
                  {
                    columnKey: 'value',
                    headText: label('game-information.column.value'),
                    render: (entry) => entry.setting_value,
                  },
                ]}
              />
            </section>
          ))}
        </>
      )}

      {webVote !== null && webVote.enabled && (
        <p>
          {label('game-information.vote.description', { gem: webVote.gem_reward })}{' '}
          <button
            type="button"
            className="lotdg-button"
            disabled={!webVote.can_claim}
            onClick={() => void claim()}
          >
            {label('game-information.vote.action')}
          </button>
        </p>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
