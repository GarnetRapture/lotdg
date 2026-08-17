import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgBountyBoardSchema,
  lotdgBountyPlacementSchema,
  lotdgBountySearchSchema,
  type LotdgBountyBoard,
  type LotdgBountySearch,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_LOCATION_LABEL_PATH } from '../../shared/constant/lotdg-legacy-code'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgCommentaryBoard } from './LotdgCommentaryBoard'

export function LotdgBountyScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [board, setBoard] = useState<LotdgBountyBoard | null>(null)
  const [search, setSearch] = useState<LotdgBountySearch | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [targetCharacterId, setTargetCharacterId] = useState(0)
  const [amount, setAmount] = useState('')
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/bounty/${characterId}/inspect`, lotdgBountyBoardSchema)
      .then(setBoard)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const searchTarget = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (searchTerm.trim() === '') {
      return
    }

    try {
      setSearch(
        await getJson(
          `/bounty/${characterId}/search?search_term=${encodeURIComponent(searchTerm.trim())}`,
          lotdgBountySearchSchema,
        ),
      )
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const placeBounty = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    const parsedAmount = Number.parseInt(amount, 10)

    if (targetCharacterId <= 0 || Number.isNaN(parsedAmount)) {
      setMessage(label('bounty.error.incomplete-form'))

      return
    }

    try {
      const result = await postForm(`/bounty/${characterId}/place`, lotdgBountyPlacementSchema, {
        target_character_id: targetCharacterId,
        amount: parsedAmount,
      })

      if (!result.placed) {
        setMessage(
          resolveMessageKeyLabel(result.message_key, translate, {
            minimum: result.minimum ?? 0,
            maximum: result.maximum ?? 0,
            currentBounty: result.current_bounty ?? 0,
            totalCost: result.total_cost ?? 0,
          }),
        )
      } else {
        setMessage(
          label('bounty.placed', {
            target: result.target_display_name ?? '',
            amount: result.amount ?? 0,
            fee: result.listing_fee ?? 0,
            totalCost: result.total_cost ?? 0,
          }),
        )
        setAmount('')
        setTargetCharacterId(0)
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('bounty.title')}</h2>

      {board !== null && (
        <>
          <p>{label('bounty.description')}</p>

          <p>
            {label('bounty.rule', {
              minimumPerLevel: board.minimum_per_level,
              maximumPerLevel: board.maximum_per_level,
              feePercent: board.listing_fee_percent,
              minimumLevel: board.minimum_target_level,
              placedToday: board.bounty_set_today,
              maximumPerDay: board.maximum_bounty_per_day,
            })}
          </p>

          {board.own_bounty > 0 && (
            <p className="colLtRed">{label('bounty.own', { bounty: board.own_bounty })}</p>
          )}

          <LotdgDataTable
            rowList={board.bounty_list}
            rowKey={(entry) => entry.character_id}
            emptyText={label('bounty.empty')}
            columnList={[
              {
                columnKey: 'name',
                headText: label('bounty.column.name'),
                render: (entry) => entry.display_name,
              },
              {
                columnKey: 'level',
                headText: label('bounty.column.level'),
                render: (entry) => entry.level,
              },
              {
                columnKey: 'bounty',
                headText: label('bounty.column.bounty'),
                render: (entry) => entry.bounty,
              },
              {
                columnKey: 'location',
                headText: label('bounty.column.location'),
                render: (entry) =>
                  translate(
                    LOTDG_LOCALE_NAMESPACE.COMMON,
                    LOTDG_LOCATION_LABEL_PATH[entry.location_code] ?? 'location.field',
                  ),
              },
              {
                columnKey: 'state',
                headText: label('bounty.column.state'),
                render: (entry) =>
                  label(
                    entry.is_alive === false
                      ? 'bounty.state.dead'
                      : entry.is_logged_in
                        ? 'bounty.state.online'
                        : 'bounty.state.offline',
                  ),
              },
              {
                columnKey: 'action',
                headText: label('bounty.column.action'),
                render: (entry) => (
                  <button
                    type="button"
                    className="lotdg-button"
                    onClick={() => setTargetCharacterId(entry.character_id)}
                  >
                    {label('bounty.action.select')}
                  </button>
                ),
              },
            ]}
          />

          <form onSubmit={(event) => void searchTarget(event)}>
            <p>
              <label htmlFor="lotdg-bounty-search">{label('bounty.form.search')}</label>{' '}
              <input
                id="lotdg-bounty-search"
                className="lotdg-input"
                value={searchTerm}
                onChange={(event) => setSearchTerm(event.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('bounty.action.search')}
              </button>
            </p>
          </form>

          {search !== null && (
            <LotdgDataTable
              rowList={search.candidate_list}
              rowKey={(candidate) => candidate.character_id}
              emptyText={label('bounty.search-empty')}
              columnList={[
                {
                  columnKey: 'name',
                  headText: label('bounty.column.name'),
                  render: (candidate) => candidate.display_name,
                },
                {
                  columnKey: 'level',
                  headText: label('bounty.column.level'),
                  render: (candidate) => candidate.level,
                },
                {
                  columnKey: 'range',
                  headText: label('bounty.column.range'),
                  render: (candidate) =>
                    label('bounty.range-value', {
                      minimum: candidate.minimum_bounty,
                      remaining: candidate.remaining_bounty,
                    }),
                },
                {
                  columnKey: 'action',
                  headText: label('bounty.column.action'),
                  render: (candidate) => (
                    <button
                      type="button"
                      className="lotdg-button"
                      disabled={!candidate.eligible}
                      onClick={() => setTargetCharacterId(candidate.character_id)}
                    >
                      {label('bounty.action.select')}
                    </button>
                  ),
                },
              ]}
            />
          )}

          <form onSubmit={(event) => void placeBounty(event)}>
            <p>
              <label htmlFor="lotdg-bounty-target">{label('bounty.form.target')}</label>{' '}
              <select
                id="lotdg-bounty-target"
                className="lotdg-select"
                value={targetCharacterId}
                onChange={(event) => setTargetCharacterId(Number(event.target.value))}
              >
                <option value={0}>{label('bounty.form.target-unselected')}</option>
                {[
                  ...board.bounty_list.map((entry) => ({
                    character_id: entry.character_id,
                    display_name: entry.display_name,
                  })),
                  ...(search?.candidate_list ?? [])
                    .filter(
                      (candidate) =>
                        candidate.eligible &&
                        !board.bounty_list.some(
                          (entry) => entry.character_id === candidate.character_id,
                        ),
                    )
                    .map((candidate) => ({
                      character_id: candidate.character_id,
                      display_name: candidate.display_name,
                    })),
                ].map((option) => (
                  <option key={option.character_id} value={option.character_id}>
                    {option.display_name}
                  </option>
                ))}
              </select>{' '}
              <label htmlFor="lotdg-bounty-amount">{label('bounty.form.amount')}</label>{' '}
              <input
                id="lotdg-bounty-amount"
                className="lotdg-input"
                inputMode="numeric"
                value={amount}
                onChange={(event) => setAmount(event.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('bounty.action.place')}
              </button>
            </p>
          </form>
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard characterId={characterId} sectionCode="dag" />
    </section>
  )
}
