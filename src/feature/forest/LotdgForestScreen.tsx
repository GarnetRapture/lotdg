import { useState } from 'react'
import { postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgBattleRoundSchema,
  lotdgForestEncounterSchema,
  type LotdgBattleRound,
  type LotdgForestEncounter,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_SPECIAL_EVENT_CODE_LIST,
  type LotdgSpecialEventCode,
} from '../../shared/constant/lotdg-special-event-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

const SEARCH_TYPE_LIST = ['normal', 'slum', 'thrill'] as const

export function LotdgForestScreen({
  characterId,
  onStateChange,
  onSpecialEventOpen,
}: LotdgMutableScreenProps & {
  readonly onSpecialEventOpen: (eventCode: LotdgSpecialEventCode) => void
}) {
  const { translate } = useLotdgLocale()
  const [encounter, setEncounter] = useState<LotdgForestEncounter | null>(null)
  const [roundList, setRoundList] = useState<LotdgBattleRound[]>([])
  const [errorMessage, setErrorMessage] = useState('')

  const search = async (searchType: string) => {
    setErrorMessage('')
    setRoundList([])

    try {
      const result = await postForm(`/forest/${characterId}/search`, lotdgForestEncounterSchema, {
        search_type: searchType,
      })

      setEncounter(result)
      onStateChange()
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    }
  }

  const fight = async () => {
    try {
      const result = await postForm(`/forest/${characterId}/fight`, lotdgBattleRoundSchema)
      setRoundList((previous) => [...previous, result])
      onStateChange()

      if (result.victory === true || result.defeat === true) {
        setEncounter(null)
      }
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    }
  }

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.FOREST, path, valueMap)

  return (
    <section>
      <h2>{label('title')}</h2>
      <p>{label('description')}</p>

      <p>
        {SEARCH_TYPE_LIST.map((searchType) => (
          <button
            key={searchType}
            type="button"
            className="lotdg-button"
            onClick={() => void search(searchType)}
          >
            {label(`action.search-${searchType}`)}
          </button>
        ))}
      </p>

      <p>
        {LOTDG_SPECIAL_EVENT_CODE_LIST.map((eventCode) => (
          <button
            key={eventCode}
            type="button"
            className="lotdg-button"
            onClick={() => onSpecialEventOpen(eventCode)}
          >
            {label(`special.${eventCode}.title`)}
          </button>
        ))}
      </p>

      {errorMessage !== '' && <p className="colLtRed">{errorMessage}</p>}

      {encounter?.encountered === false && encounter.message_key !== undefined && (
        <p className="colLtRed">{resolveMessageKeyLabel(encounter.message_key, translate)}</p>
      )}

      {encounter?.enemy !== undefined && (
        <div>
          <p className="colLtYellow">
            {label('encounter', {
              name: encounter.enemy.creature_name,
              weapon: encounter.enemy.weapon_name,
            })}
          </p>
          <p>
            {label('enemy-status', {
              level: encounter.enemy.creature_level,
              health: encounter.enemy.health,
            })}
          </p>
          {encounter.enemy_first_strike === true && (
            <p className="colLtRed">{label('enemy-first-strike')}</p>
          )}
          <button type="button" className="lotdg-button" onClick={() => void fight()}>
            {label('action.fight')}
          </button>
        </div>
      )}

      {roundList.map((round, index) => (
        <p key={index}>
          {label('round-result', {
            round: index + 1,
            damageToEnemy: round.damage_to_enemy ?? 0,
            damageToPlayer: round.damage_to_player ?? 0,
            playerHitPoint: round.player_hit_point ?? 0,
            enemyHitPoint: round.enemy_hit_point ?? 0,
          })}
          {round.critical_attack === true && (
            <span className="colLtYellow"> {label('critical')}</span>
          )}
          {round.victory === true && (
            <span className="colLtGreen">
              {' '}
              {label('victory', {
                gold: round.reward?.gold ?? 0,
                experience: round.reward?.experience ?? 0,
              })}
            </span>
          )}
          {round.defeat === true && <span className="colLtRed"> {label('defeat')}</span>}
        </p>
      ))}
    </section>
  )
}
