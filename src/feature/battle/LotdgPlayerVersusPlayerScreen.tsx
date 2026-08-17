import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgPvpAttackSchema,
  lotdgPvpListSchema,
  type LotdgPvpList,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgButton,
  LotdgDataTable,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'

export function LotdgPlayerVersusPlayerScreen({
  characterId,
  onStateChange,
}: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [targetList, setTargetList] = useState<LotdgPvpList | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/pvp/${characterId}/list`, lotdgPvpListSchema)
      .then(setTargetList)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.BATTLE, path, valueMap)

  const attack = async (targetCharacterId: number) => {
    try {
      const result = await postForm(`/pvp/${characterId}/attack`, lotdgPvpAttackSchema, {
        target_character_id: targetCharacterId,
      })

      if (!result.attacked) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
      } else if (result.victory === true) {
        setMessage(
          label('pvp.victory', {
            name: result.defender_display_name ?? '',
            gold: result.gold_looted ?? 0,
            experience: result.experience_gained ?? 0,
          }),
        )
      } else {
        setMessage(label('pvp.defeat', { gold: result.gold_lost ?? 0 }))
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('pvp.title')}>
      {targetList !== null && (
        <>
          <LotdgText>{label('pvp.remaining', { count: targetList.player_fight })}</LotdgText>

          <LotdgDataTable
            rowList={targetList.target_list}
            rowKey={(target) => target.character_id}
            columnList={[
              {
                columnKey: 'name',
                headText: label('pvp.column.name'),
                render: (target) => target.display_name,
              },
              {
                columnKey: 'level',
                headText: label('pvp.column.level'),
                render: (target) => target.level,
              },
              {
                columnKey: 'action',
                headText: label('pvp.column.action'),
                render: (target) => (
                  <LotdgButton
                    labelSlot={label('pvp.action.attack')}
                    isDisabled={!target.attackable}
                    onSelect={() => void attack(target.character_id)}
                  />
                ),
              },
            ]}
          />
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
