import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgHallOfFameSchema,
  type LotdgHallOfFame,
  type LotdgRankedEntry,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'

interface LotdgHallOfFameSection {
  readonly sectionKey: keyof LotdgHallOfFame
  readonly valueColumnKey: string
  readonly readValue: (entry: LotdgRankedEntry) => number
}

const SECTION_LIST: readonly LotdgHallOfFameSection[] = [
  {
    sectionKey: 'dragon_slayer',
    valueColumnKey: 'dragon-kill',
    readValue: (entry) => entry.dragon_kill_count ?? 0,
  },
  { sectionKey: 'top_warrior', valueColumnKey: 'level', readValue: (entry) => entry.level ?? 0 },
  {
    sectionKey: 'wealthiest',
    valueColumnKey: 'gold',
    readValue: (entry) => (entry.gold ?? 0) + (entry.gold_in_bank ?? 0),
  },
  {
    sectionKey: 'strongest',
    valueColumnKey: 'power',
    readValue: (entry) => (entry.attack_point ?? 0) + (entry.defence_point ?? 0),
  },
  {
    sectionKey: 'bounty_hunter',
    valueColumnKey: 'player-kill',
    readValue: (entry) => entry.player_kill_count ?? 0,
  },
  {
    sectionKey: 'most_resurrected',
    valueColumnKey: 'resurrection',
    readValue: (entry) => entry.resurrection_count ?? 0,
  },
  {
    sectionKey: 'most_active',
    valueColumnKey: 'generation',
    readValue: (entry) => entry.generation_count ?? 0,
  },
]

export function LotdgHallOfFameScreen() {
  const { translate } = useLotdgLocale()
  const [hallOfFame, setHallOfFame] = useState<LotdgHallOfFame | null>(null)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    let isMounted = true

    getJson('/hall-of-fame', lotdgHallOfFameSchema)
      .then((result) => {
        if (isMounted) {
          setHallOfFame(result)
        }
      })
      .catch((error: unknown) => {
        if (isMounted) {
          setErrorMessage(resolveErrorLabel(error, translate))
        }
      })

    return () => {
      isMounted = false
    }
  }, [translate])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  return (
    <section>
      <h2>{label('hall-of-fame.title')}</h2>

      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />

      {hallOfFame !== null &&
        SECTION_LIST.map((section) => (
          <section key={section.sectionKey}>
            <h3>{label(`hall-of-fame.section.${section.sectionKey}`)}</h3>

            <LotdgDataTable
              rowList={hallOfFame[section.sectionKey]}
              rowKey={(entry) => `${section.sectionKey}-${entry.rank}-${entry.display_name}`}
              emptyText={label('hall-of-fame.empty')}
              columnList={[
                {
                  columnKey: 'rank',
                  headText: label('hall-of-fame.column.rank'),
                  render: (entry) => entry.rank,
                },
                {
                  columnKey: 'name',
                  headText: label('hall-of-fame.column.name'),
                  render: (entry) => entry.display_name,
                },
                {
                  columnKey: section.valueColumnKey,
                  headText: label(`hall-of-fame.column.${section.valueColumnKey}`),
                  render: (entry) => section.readValue(entry),
                },
              ]}
            />
          </section>
        ))}
    </section>
  )
}
