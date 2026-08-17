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

export function LotdgPlayerVersusPlayerScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
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
    <section>
      <h2>{label('pvp.title')}</h2>

      {targetList !== null && (
        <>
          <p>{label('pvp.remaining', { count: targetList.player_fight })}</p>

          <table className="lotdg-stat">
            <tbody>
              <tr>
                <th className="lotdg-stat__head">{label('pvp.column.name')}</th>
                <th className="lotdg-stat__head">{label('pvp.column.level')}</th>
                <th className="lotdg-stat__head">{label('pvp.column.action')}</th>
              </tr>
              {targetList.target_list.map((target) => (
                <tr key={target.character_id}>
                  <td className="lotdg-stat__value">{target.display_name}</td>
                  <td className="lotdg-stat__value">{target.level}</td>
                  <td className="lotdg-stat__value">
                    <button
                      type="button"
                      className="lotdg-button"
                      onClick={() => void attack(target.character_id)}
                      disabled={!target.attackable}
                    >
                      {label('pvp.action.attack')}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}

      {message !== '' && <p className="colLtYellow">{message}</p>}
    </section>
  )
}
