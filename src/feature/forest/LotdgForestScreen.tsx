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
import { LOTDG_SPECIAL_EVENT_CODE_LIST } from '../../shared/constant/lotdg-special-event-code'
import {
  LOTDG_FOREST_SEARCH_TYPE_CODE_LIST,
  type LotdgForestSearchTypeCode,
} from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgForestScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgInlineText,
  LotdgScreen,
  LotdgSection,
  LotdgText,
} from '../../shared/ui'

export function LotdgForestScreen({
  characterId,
  onStateChange,
  onSpecialEventOpen,
}: LotdgForestScreenProps) {
  const { translate } = useLotdgLocale()
  const [encounter, setEncounter] = useState<LotdgForestEncounter | null>(null)
  const [roundList, setRoundList] = useState<LotdgBattleRound[]>([])
  const [errorMessage, setErrorMessage] = useState('')

  const search = async (searchType: LotdgForestSearchTypeCode) => {
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
    <LotdgScreen titleText={label('title')}>
      <LotdgText>{label('description')}</LotdgText>

      <LotdgActionRow>
        {LOTDG_FOREST_SEARCH_TYPE_CODE_LIST.map((searchType) => (
          <LotdgButton
            key={searchType}
            labelSlot={label(`action.search-${searchType}`)}
            onSelect={() => void search(searchType)}
          />
        ))}
      </LotdgActionRow>

      <LotdgActionRow>
        {LOTDG_SPECIAL_EVENT_CODE_LIST.map((eventCode) => (
          <LotdgButton
            key={eventCode}
            labelSlot={label(`special.${eventCode}.title`)}
            onSelect={() => onSpecialEventOpen(eventCode)}
          />
        ))}
      </LotdgActionRow>

      {errorMessage !== '' && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>{errorMessage}</LotdgText>
      )}

      {encounter?.encountered === false && encounter.message_key !== undefined && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
          {resolveMessageKeyLabel(encounter.message_key, translate)}
        </LotdgText>
      )}

      {encounter?.enemy !== undefined && (
        <LotdgSection>
          <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
            {label('encounter', {
              name: encounter.enemy.creature_name,
              weapon: encounter.enemy.weapon_name,
            })}
          </LotdgText>
          <LotdgText>
            {label('enemy-status', {
              level: encounter.enemy.creature_level,
              health: encounter.enemy.health,
            })}
          </LotdgText>
          {encounter.enemy_first_strike === true && (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {label('enemy-first-strike')}
            </LotdgText>
          )}
          <LotdgActionRow>
            <LotdgButton labelSlot={label('action.fight')} onSelect={() => void fight()} />
          </LotdgActionRow>
        </LotdgSection>
      )}

      {roundList.map((round, index) => (
        <LotdgText key={index}>
          {label('round-result', {
            round: index + 1,
            damageToEnemy: round.damage_to_enemy ?? 0,
            damageToPlayer: round.damage_to_player ?? 0,
            playerHitPoint: round.player_hit_point ?? 0,
            enemyHitPoint: round.enemy_hit_point ?? 0,
          })}
          {round.critical_attack === true && (
            <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
              {' '}
              {label('critical')}
            </LotdgInlineText>
          )}
          {round.victory === true && (
            <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
              {' '}
              {label('victory', {
                gold: round.reward?.gold ?? 0,
                experience: round.reward?.experience ?? 0,
              })}
            </LotdgInlineText>
          )}
          {round.defeat === true && (
            <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {' '}
              {label('defeat')}
            </LotdgInlineText>
          )}
        </LotdgText>
      ))}
    </LotdgScreen>
  )
}
