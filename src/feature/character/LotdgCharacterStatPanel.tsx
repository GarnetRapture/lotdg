import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgCharacterPanelSchema,
  type LotdgCharacterPanel,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

const SPIRIT_LABEL_PATH: Record<number, string> = {
  [-6]: 'spirit.dead',
  [-2]: 'spirit.very-low',
  [-1]: 'spirit.low',
  0: 'spirit.normal',
  1: 'spirit.high',
  2: 'spirit.very-high',
}

export function LotdgCharacterStatPanel({
  characterId,
  refreshToken,
}: {
  readonly characterId: number
  readonly refreshToken: number
}) {
  const { translate } = useLotdgLocale()
  const [panel, setPanel] = useState<LotdgCharacterPanel | null>(null)

  useEffect(() => {
    let isMounted = true

    getJson(`/character/${characterId}`, lotdgCharacterPanelSchema)
      .then((result) => {
        if (isMounted) {
          setPanel(result)
        }
      })
      .catch(() => {
        if (isMounted) {
          setPanel(null)
        }
      })

    return () => {
      isMounted = false
    }
  }, [characterId, refreshToken])

  if (panel === null) {
    return null
  }

  const isAlive = panel.vital.is_alive === 1
  const label = (labelPath: string) => translate(LOTDG_LOCALE_NAMESPACE.CHARACTER_STAT, labelPath)

  return (
    <table className="lotdg-stat">
      <tbody>
        <tr>
          <th className="lotdg-stat__head" colSpan={2}>
            {label('section.basic-information')}
          </th>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.name')}</td>
          <td className="lotdg-stat__value">{panel.character.display_name}</td>
        </tr>
        {isAlive ? (
          <>
            <tr>
              <td className="lotdg-stat__label">{label('field.hit-point')}</td>
              <td className="lotdg-stat__value">
                {panel.vital.hit_point}/{panel.vital.max_hit_point}
              </td>
            </tr>
            <tr>
              <td className="lotdg-stat__label">{label('field.turn')}</td>
              <td className="lotdg-stat__value">{panel.daily_allowance.forest_turn}</td>
            </tr>
          </>
        ) : (
          <>
            <tr>
              <td className="lotdg-stat__label">{label('field.soul-point')}</td>
              <td className="lotdg-stat__value">{panel.vital.soul_point}</td>
            </tr>
            <tr>
              <td className="lotdg-stat__label">{label('field.grave-fight')}</td>
              <td className="lotdg-stat__value">{panel.vital.grave_fight}</td>
            </tr>
          </>
        )}
        <tr>
          <td className="lotdg-stat__label">{label('field.spirit')}</td>
          <td className="lotdg-stat__value">
            {label(SPIRIT_LABEL_PATH[panel.vital.spirit_level] ?? 'spirit.normal')}
          </td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.level')}</td>
          <td className="lotdg-stat__value">{panel.character.level}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.attack')}</td>
          <td className="lotdg-stat__value">{panel.combat_stat.attack_point}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.defence')}</td>
          <td className="lotdg-stat__value">{panel.combat_stat.defence_point}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.gem')}</td>
          <td className="lotdg-stat__value">{panel.wealth.gem}</td>
        </tr>

        <tr>
          <th className="lotdg-stat__head" colSpan={2}>
            {label('section.other-information')}
          </th>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.gold')}</td>
          <td className="lotdg-stat__value">{panel.wealth.gold}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.experience')}</td>
          <td className="lotdg-stat__value">{panel.progression.experience}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.weapon')}</td>
          <td className="lotdg-stat__value">{panel.equipment.weapon_name}</td>
        </tr>
        <tr>
          <td className="lotdg-stat__label">{label('field.armor')}</td>
          <td className="lotdg-stat__value">{panel.equipment.armor_name}</td>
        </tr>
      </tbody>
    </table>
  )
}
