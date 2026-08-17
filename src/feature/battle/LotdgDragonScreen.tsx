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
import type { z } from 'zod'

type DragonEnter = z.infer<typeof lotdgDragonEnterSchema>
type DragonRound = z.infer<typeof lotdgDragonRoundSchema>
type DragonRebirth = z.infer<typeof lotdgDragonRebirthSchema>

export function LotdgDragonScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
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
          { flawless: result.flawless === true ? '1' : '0' },
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
    <section>
      <h2>{label('dragon.title')}</h2>
      <p>{label('dragon.description')}</p>

      <p>
        <button type="button" className="lotdg-button" onClick={() => void enterLair()}>
          {label('dragon.action.enter')}
        </button>{' '}
        {lair?.entered === true && (
          <button type="button" className="lotdg-button" onClick={() => void fight()}>
            {label('dragon.action.fight')}
          </button>
        )}
      </p>

      {lair?.dragon !== undefined && (
        <p className="colLtGreen">
          {label('dragon.status', {
            health: lair.dragon.health,
            attack: lair.dragon.attack_point,
            defense: lair.dragon.defense_point,
          })}
        </p>
      )}

      {roundList.map((round, index) => (
        <p key={index}>
          {label('dragon.round', {
            round: index + 1,
            damageToDragon: round.damage_to_dragon ?? 0,
            damageToPlayer: round.damage_to_player ?? 0,
            playerHitPoint: round.player_hit_point ?? 0,
            dragonHitPoint: round.dragon_hit_point ?? 0,
          })}
          {round.defeat === true && <span className="colLtRed"> {label('dragon.defeat')}</span>}
        </p>
      ))}

      {rebirth !== null && (
        <p className="colLtGreen">
          {label('dragon.rebirth', {
            count: rebirth.dragon_kill_count,
            title: rebirth.new_title,
            gold: rebirth.gold,
            gem: rebirth.gem_gain,
          })}
        </p>
      )}

      {message !== '' && <p className="colLtRed">{message}</p>}
    </section>
  )
}
