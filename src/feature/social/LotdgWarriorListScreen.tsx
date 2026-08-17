import { useCallback, useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgWarriorListSchema,
  type LotdgWarriorList,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_LOCATION_LABEL_PATH } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgWarriorListScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgButton,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgForm,
  LotdgLink,
  LotdgNoticeLine,
  LotdgPaginationRow,
  LotdgScreen,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_WARRIOR_LIST_MODE_CODE = { ONLINE: 'online', PAGE: 'page' } as const

const LOTDG_WARRIOR_LIST_FIRST_PAGE = 1

const LOTDG_BIOGRAPHY_HASH_PREFIX = 'biography-'

function buildSearchQueryString(searchTerm: string): string {
  return searchTerm.trim() === '' ? '' : `?search_term=${encodeURIComponent(searchTerm.trim())}`
}

function buildPageQueryString(pageNumber: number): string {
  return `?page=${pageNumber}`
}

export function LotdgWarriorListScreen({ onBiographyOpen }: LotdgWarriorListScreenProps) {
  const { translate } = useLotdgLocale()
  const [warriorList, setWarriorList] = useState<LotdgWarriorList | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [queryString, setQueryString] = useState('')
  const [errorMessage, setErrorMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/warrior-list${queryString}`, lotdgWarriorListSchema)
      .then(setWarriorList)
      .catch((error: unknown) => {
        setErrorMessage(resolveErrorLabel(error, translate))
      })
  }, [queryString, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const submitSearch = () => {
    setQueryString(buildSearchQueryString(searchTerm))
  }

  return (
    <LotdgScreen titleText={label('warrior-list.title')}>
      <LotdgForm onSubmit={submitSearch}>
        <LotdgFieldRow>
          <LotdgTextField
            labelText={label('warrior-list.search')}
            value={searchTerm}
            onValueChange={setSearchTerm}
          />
          <LotdgSubmitButton labelSlot={label('warrior-list.action.search')} />
          <LotdgButton
            labelSlot={label('warrior-list.action.online')}
            onSelect={() => setQueryString('')}
          />
          <LotdgButton
            labelSlot={label('warrior-list.action.all')}
            onSelect={() => setQueryString(buildPageQueryString(LOTDG_WARRIOR_LIST_FIRST_PAGE))}
          />
        </LotdgFieldRow>
      </LotdgForm>

      {warriorList !== null && (
        <>
          <LotdgText>
            {label('warrior-list.summary', {
              total: warriorList.total_player_count,
              shown: warriorList.warrior_list.length,
            })}
          </LotdgText>

          {warriorList.truncated === true && (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
              {label('warrior-list.truncated')}
            </LotdgText>
          )}

          <LotdgDataTable
            rowList={warriorList.warrior_list}
            rowKey={(warrior) => warrior.character_id}
            emptyText={label('warrior-list.empty')}
            columnList={[
              {
                columnKey: 'name',
                headText: label('warrior-list.column.name'),
                render: (warrior) => (
                  <LotdgLink
                    hashCode={`${LOTDG_BIOGRAPHY_HASH_PREFIX}${warrior.character_id}`}
                    labelSlot={warrior.display_name}
                    onSelect={() => onBiographyOpen(warrior.character_id)}
                  />
                ),
              },
              {
                columnKey: 'level',
                headText: label('warrior-list.column.level'),
                render: (warrior) => warrior.level,
              },
              {
                columnKey: 'location',
                headText: label('warrior-list.column.location'),
                render: (warrior) =>
                  translate(
                    LOTDG_LOCALE_NAMESPACE.COMMON,
                    LOTDG_LOCATION_LABEL_PATH[warrior.location_code] ?? 'location.field',
                  ),
              },
              {
                columnKey: 'state',
                headText: label('warrior-list.column.state'),
                render: (warrior) =>
                  warrior.is_alive === false
                    ? label('warrior-list.state.dead')
                    : warrior.is_online
                      ? label('warrior-list.state.online')
                      : warrior.days_since_last_seen === null
                        ? label('warrior-list.state.offline')
                        : label('warrior-list.state.away', {
                            day: warrior.days_since_last_seen,
                          }),
              },
            ]}
          />

          {warriorList.mode === LOTDG_WARRIOR_LIST_MODE_CODE.PAGE &&
            warriorList.page_count !== undefined && (
              <LotdgPaginationRow
                pageCount={warriorList.page_count}
                activePageIndex={(warriorList.page ?? LOTDG_WARRIOR_LIST_FIRST_PAGE) - 1}
                pageLabelText={(pageNumber) => String(pageNumber)}
                onPageSelect={(pageIndex) =>
                  setQueryString(buildPageQueryString(pageIndex + LOTDG_WARRIOR_LIST_FIRST_PAGE))
                }
              />
            )}
        </>
      )}

      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />
    </LotdgScreen>
  )
}
