import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgCharacterPanelSchema,
  type LotdgCharacterPanel,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type {
  LotdgStatEntry,
  LotdgStatSection,
} from '../../shared/type/lotdg-ui-component-contract'
import { LotdgStatBuffList, LotdgStatTable } from '../../shared/ui'

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
  const label = (labelPath: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.CHARACTER_STAT, labelPath, valueMap)
  const buffEntryList = Object.entries(panel.combat_stat.buff_list_json ?? {})

  const vitalEntryList: ReadonlyArray<LotdgStatEntry> = isAlive
    ? [
        {
          entryKey: 'hit-point',
          labelText: label('field.hit-point'),
          valueSlot: `${panel.vital.hit_point}/${panel.vital.max_hit_point}`,
        },
        {
          entryKey: 'turn',
          labelText: label('field.turn'),
          valueSlot: panel.daily_allowance.forest_turn,
        },
      ]
    : [
        {
          entryKey: 'soul-point',
          labelText: label('field.soul-point'),
          valueSlot: panel.vital.soul_point,
        },
        {
          entryKey: 'grave-fight',
          labelText: label('field.grave-fight'),
          valueSlot: panel.vital.grave_fight,
        },
      ]

  const sectionList: ReadonlyArray<LotdgStatSection> = [
    {
      sectionKey: 'basic-information',
      headText: label('section.basic-information'),
      entryList: [
        {
          entryKey: 'name',
          labelText: label('field.name'),
          valueSlot: panel.character.display_name,
        },
        ...vitalEntryList,
        {
          entryKey: 'spirit',
          labelText: label('field.spirit'),
          valueSlot: label(SPIRIT_LABEL_PATH[panel.vital.spirit_level] ?? 'spirit.normal'),
        },
        { entryKey: 'level', labelText: label('field.level'), valueSlot: panel.character.level },
        {
          entryKey: 'attack',
          labelText: label('field.attack'),
          valueSlot: panel.combat_stat.attack_point,
        },
        {
          entryKey: 'defence',
          labelText: label('field.defence'),
          valueSlot: panel.combat_stat.defence_point,
        },
        { entryKey: 'gem', labelText: label('field.gem'), valueSlot: panel.wealth.gem },
      ],
    },
    {
      sectionKey: 'other-information',
      headText: label('section.other-information'),
      entryList: [
        { entryKey: 'gold', labelText: label('field.gold'), valueSlot: panel.wealth.gold },
        {
          entryKey: 'experience',
          labelText: label('field.experience'),
          valueSlot: panel.progression.experience,
        },
        {
          entryKey: 'weapon',
          labelText: label('field.weapon'),
          valueSlot: panel.equipment.weapon_name,
        },
        {
          entryKey: 'armor',
          labelText: label('field.armor'),
          valueSlot: panel.equipment.armor_name,
        },
      ],
    },
  ]

  return (
    <LotdgStatTable
      sectionList={sectionList}
      footerSlot={
        <LotdgStatBuffList
          titleText={label('field.buff')}
          emptyText={label('buff.none')}
          buffList={buffEntryList.map(([buffKey, buff]) => ({
            buffKey,
            nameSlot: parseLegacyMarkup(buff.name ?? buffKey),
            roundsText: label('buff.rounds-left', { rounds: buff.rounds ?? 0 }),
          }))}
        />
      }
    />
  )
}
