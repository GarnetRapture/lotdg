import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgGraveyardActionSchema,
  lotdgGraveyardInspectSchema,
  type LotdgGraveyardInspect,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type { z } from 'zod'

type GraveyardAction = z.infer<typeof lotdgGraveyardActionSchema>

export function LotdgGraveyardScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
  const { translate } = useLotdgLocale()
  const [inspect, setInspect] = useState<LotdgGraveyardInspect | null>(null)
  const [lastAction, setLastAction] = useState<GraveyardAction | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/graveyard/${characterId}/inspect`, lotdgGraveyardInspectSchema)
      .then(setInspect)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.BATTLE, path, valueMap)

  const act = async (action: string, body: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(
        `/graveyard/${characterId}/${action}`,
        lotdgGraveyardActionSchema,
        body,
      )

      setLastAction(result)
      setMessage(
        result.message_key === undefined
          ? ''
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
      <h2>{label('graveyard.title')}</h2>

      {inspect !== null && (
        <>
          <p>
            {label('graveyard.status', {
              soulPoint: inspect.soul_point,
              maximumSoulPoint: inspect.maximum_soul_point,
              graveFight: inspect.grave_fight,
              deathPower: inspect.death_power,
            })}
          </p>

          <p>
            <button type="button" className="lotdg-button" onClick={() => void act('search')}>
              {label('graveyard.action.search')}
            </button>{' '}
            <button type="button" className="lotdg-button" onClick={() => void act('fight')}>
              {label('graveyard.action.fight')}
            </button>{' '}
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('restore')}
              disabled={inspect.death_power < inspect.restore_favor_cost}
            >
              {label('graveyard.action.restore', { cost: inspect.restore_favor_cost })}
            </button>
          </p>
        </>
      )}

      {lastAction?.enemy !== undefined && (
        <p className="colLtYellow">
          {label('graveyard.encounter', {
            name: lastAction.enemy.creature_name,
            health: lastAction.enemy.health,
          })}
        </p>
      )}

      {lastAction?.victory === true && (
        <p className="colLtGreen">
          {label('graveyard.victory', { favor: lastAction.favor_gained ?? 0 })}
        </p>
      )}

      {lastAction?.defeat === true && <p className="colLtRed">{label('graveyard.defeat')}</p>}

      {message !== '' && <p className="colLtYellow">{message}</p>}
    </section>
  )
}
