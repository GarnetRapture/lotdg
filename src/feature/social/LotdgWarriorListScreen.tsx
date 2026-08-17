import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgWarriorListSchema,
  type LotdgWarriorList,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_LOCATION_LABEL_PATH } from '../../shared/constant/lotdg-legacy-code'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'

export function LotdgWarriorListScreen({
  onBiographyOpen,
}: {
  readonly onBiographyOpen: (characterId: number) => void
}) {
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

  const submitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setQueryString(
      searchTerm.trim() === '' ? '' : `?search_term=${encodeURIComponent(searchTerm.trim())}`,
    )
  }

  return (
    <section>
      <h2>{label('warrior-list.title')}</h2>

      <form onSubmit={submitSearch}>
        <p>
          <label htmlFor="lotdg-warrior-search">{label('warrior-list.search')}</label>{' '}
          <input
            id="lotdg-warrior-search"
            className="lotdg-input"
            value={searchTerm}
            onChange={(event) => setSearchTerm(event.target.value)}
          />{' '}
          <button type="submit" className="lotdg-button">
            {label('warrior-list.action.search')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={() => setQueryString('')}>
            {label('warrior-list.action.online')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={() => setQueryString('?page=1')}>
            {label('warrior-list.action.all')}
          </button>
        </p>
      </form>

      {warriorList !== null && (
        <>
          <p>
            {label('warrior-list.summary', {
              total: warriorList.total_player_count,
              shown: warriorList.warrior_list.length,
            })}
          </p>

          {warriorList.truncated === true && (
            <p className="colLtYellow">{label('warrior-list.truncated')}</p>
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
                  <a
                    href={`#biography-${warrior.character_id}`}
                    onClick={(event) => {
                      event.preventDefault()
                      onBiographyOpen(warrior.character_id)
                    }}
                  >
                    {warrior.display_name}
                  </a>
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

          {warriorList.mode === 'page' && warriorList.page_count !== undefined && (
            <p>
              {Array.from({ length: warriorList.page_count }, (_unused, pageIndex) => (
                <button
                  key={pageIndex + 1}
                  type="button"
                  className="lotdg-button"
                  disabled={warriorList.page === pageIndex + 1}
                  onClick={() => setQueryString(`?page=${pageIndex + 1}`)}
                >
                  {pageIndex + 1}
                </button>
              ))}
            </p>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />
    </section>
  )
}
