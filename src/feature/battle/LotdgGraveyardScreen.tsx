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
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'
import type { z } from 'zod'

type GraveyardAction = z.infer<typeof lotdgGraveyardActionSchema>

const LOTDG_GRAVEYARD_ACTION_CODE = {
  SEARCH: 'search',
  FIGHT: 'fight',
  RESTORE: 'restore',
} as const

export function LotdgGraveyardScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
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
    <LotdgScreen titleText={label('graveyard.title')}>
      {inspect !== null && (
        <>
          <LotdgText>
            {label('graveyard.status', {
              soulPoint: inspect.soul_point,
              maximumSoulPoint: inspect.maximum_soul_point,
              graveFight: inspect.grave_fight,
              deathPower: inspect.death_power,
            })}
          </LotdgText>

          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('graveyard.action.search')}
              onSelect={() => void act(LOTDG_GRAVEYARD_ACTION_CODE.SEARCH)}
            />
            <LotdgButton
              labelSlot={label('graveyard.action.fight')}
              onSelect={() => void act(LOTDG_GRAVEYARD_ACTION_CODE.FIGHT)}
            />
            <LotdgButton
              labelSlot={label('graveyard.action.restore', { cost: inspect.restore_favor_cost })}
              isDisabled={inspect.death_power < inspect.restore_favor_cost}
              onSelect={() => void act(LOTDG_GRAVEYARD_ACTION_CODE.RESTORE)}
            />
          </LotdgActionRow>
        </>
      )}

      {lastAction?.enemy !== undefined && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
          {label('graveyard.encounter', {
            name: lastAction.enemy.creature_name,
            health: lastAction.enemy.health,
          })}
        </LotdgText>
      )}

      {lastAction?.victory === true && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
          {label('graveyard.victory', { favor: lastAction.favor_gained ?? 0 })}
        </LotdgText>
      )}

      {lastAction?.defeat === true && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
          {label('graveyard.defeat')}
        </LotdgText>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
