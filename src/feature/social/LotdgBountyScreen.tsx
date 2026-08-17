import { useCallback, useEffect, useState } from 'react'
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
import { LOTDG_COMMENTARY_SECTION_CODE } from '../../shared/constant/lotdg-commentary-section-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgButton,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgForm,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSelectField,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'
import { LotdgCommentaryBoard } from './LotdgCommentaryBoard'

const LOTDG_BOUNTY_UNSELECTED_TARGET_ID = 0

const LOTDG_DECIMAL_RADIX = 10

export function LotdgBountyScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [board, setBoard] = useState<LotdgBountyBoard | null>(null)
  const [search, setSearch] = useState<LotdgBountySearch | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [targetCharacterId, setTargetCharacterId] = useState(LOTDG_BOUNTY_UNSELECTED_TARGET_ID)
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

  const searchTarget = async () => {
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

  const placeBounty = async () => {
    const parsedAmount = Number.parseInt(amount, LOTDG_DECIMAL_RADIX)

    if (targetCharacterId <= LOTDG_BOUNTY_UNSELECTED_TARGET_ID || Number.isNaN(parsedAmount)) {
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
        setTargetCharacterId(LOTDG_BOUNTY_UNSELECTED_TARGET_ID)
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('bounty.title')}>
      {board !== null && (
        <>
          <LotdgText>{label('bounty.description')}</LotdgText>

          <LotdgText>
            {label('bounty.rule', {
              minimumPerLevel: board.minimum_per_level,
              maximumPerLevel: board.maximum_per_level,
              feePercent: board.listing_fee_percent,
              minimumLevel: board.minimum_target_level,
              placedToday: board.bounty_set_today,
              maximumPerDay: board.maximum_bounty_per_day,
            })}
          </LotdgText>

          {board.own_bounty > 0 && (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {label('bounty.own', { bounty: board.own_bounty })}
            </LotdgText>
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
                  <LotdgButton
                    labelSlot={label('bounty.action.select')}
                    onSelect={() => setTargetCharacterId(entry.character_id)}
                  />
                ),
              },
            ]}
          />

          <LotdgForm onSubmit={() => void searchTarget()}>
            <LotdgFieldRow>
              <LotdgTextField
                labelText={label('bounty.form.search')}
                value={searchTerm}
                onValueChange={setSearchTerm}
              />
              <LotdgSubmitButton labelSlot={label('bounty.action.search')} />
            </LotdgFieldRow>
          </LotdgForm>

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
                    <LotdgButton
                      labelSlot={label('bounty.action.select')}
                      isDisabled={!candidate.eligible}
                      onSelect={() => setTargetCharacterId(candidate.character_id)}
                    />
                  ),
                },
              ]}
            />
          )}

          <LotdgForm onSubmit={() => void placeBounty()}>
            <LotdgFieldRow>
              <LotdgSelectField
                labelText={label('bounty.form.target')}
                value={String(targetCharacterId)}
                onValueChange={(nextValue) => setTargetCharacterId(Number(nextValue))}
                optionList={[
                  {
                    optionValue: String(LOTDG_BOUNTY_UNSELECTED_TARGET_ID),
                    labelText: label('bounty.form.target-unselected'),
                  },
                  ...board.bounty_list.map((entry) => ({
                    optionValue: String(entry.character_id),
                    labelText: entry.display_name,
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
                      optionValue: String(candidate.character_id),
                      labelText: candidate.display_name,
                    })),
                ]}
              />
              <LotdgTextField
                labelText={label('bounty.form.amount')}
                value={amount}
                onValueChange={setAmount}
                isNumeric
              />
              <LotdgSubmitButton labelSlot={label('bounty.action.place')} />
            </LotdgFieldRow>
          </LotdgForm>
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard
        characterId={characterId}
        sectionCode={LOTDG_COMMENTARY_SECTION_CODE.BOUNTY}
      />
    </LotdgScreen>
  )
}
