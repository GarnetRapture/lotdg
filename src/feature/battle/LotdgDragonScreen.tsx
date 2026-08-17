import { useState } from 'react'
import { postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgDragonEnterSchema,
  lotdgDragonRebirthSchema,
  lotdgDragonRoundSchema,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_BOOLEAN_FIELD_VALUE } from '../../shared/constant/lotdg-form-token'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgInlineText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import type { z } from 'zod'

type DragonEnter = z.infer<typeof lotdgDragonEnterSchema>
type DragonRound = z.infer<typeof lotdgDragonRoundSchema>
type DragonRebirth = z.infer<typeof lotdgDragonRebirthSchema>

export function LotdgDragonScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [lair, setLair] = useState<DragonEnter | null>(null)
  const [roundList, setRoundList] = useState<DragonRound[]>([])
  const [rebirth, setRebirth] = useState<DragonRebirth | null>(null)
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.BATTLE, path, valueMap)

  const enterLair = async () => {
    setRoundList([])
    setRebirth(null)

    try {
      const result = await postForm(`/dragon/${characterId}/enter`, lotdgDragonEnterSchema)
      setLair(result)
      setMessage(result.entered ? '' : resolveMessageKeyLabel(result.message_key, translate))
      onStateChange()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const fight = async () => {
    try {
      const result = await postForm(`/dragon/${characterId}/fight`, lotdgDragonRoundSchema)
      setRoundList((previous) => [...previous, result])
      onStateChange()

      if (result.victory === true) {
        const rebirthResult = await postForm(
          `/dragon/${characterId}/rebirth`,
          lotdgDragonRebirthSchema,
          {
            flawless:
              result.flawless === true
                ? LOTDG_BOOLEAN_FIELD_VALUE.TRUE
                : LOTDG_BOOLEAN_FIELD_VALUE.FALSE,
          },
        )
        setRebirth(rebirthResult)
        setLair(null)
        onStateChange()
      }

      if (result.defeat === true) {
        setLair(null)
      }
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('dragon.title')}>
      <LotdgText>{label('dragon.description')}</LotdgText>

      <LotdgActionRow>
        <LotdgButton labelSlot={label('dragon.action.enter')} onSelect={() => void enterLair()} />
        {lair?.entered === true && (
          <LotdgButton labelSlot={label('dragon.action.fight')} onSelect={() => void fight()} />
        )}
      </LotdgActionRow>

      {lair?.dragon !== undefined && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
          {label('dragon.status', {
            health: lair.dragon.health,
            attack: lair.dragon.attack_point,
            defense: lair.dragon.defense_point,
          })}
        </LotdgText>
      )}

      {roundList.map((round, index) => (
        <LotdgText key={index}>
          {label('dragon.round', {
            round: index + 1,
            damageToDragon: round.damage_to_dragon ?? 0,
            damageToPlayer: round.damage_to_player ?? 0,
            playerHitPoint: round.player_hit_point ?? 0,
            dragonHitPoint: round.dragon_hit_point ?? 0,
          })}
          {round.defeat === true && (
            <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {' '}
              {label('dragon.defeat')}
            </LotdgInlineText>
          )}
        </LotdgText>
      ))}

      {rebirth !== null && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
          {label('dragon.rebirth', {
            count: rebirth.dragon_kill_count,
            title: rebirth.new_title,
            gold: rebirth.gold,
            gem: rebirth.gem_gain,
          })}
        </LotdgText>
      )}

      <LotdgNoticeLine messageText={message} tone={LOTDG_NOTICE_TONE.FAILURE} />
    </LotdgScreen>
  )
}
