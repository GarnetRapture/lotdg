import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgAdministrationAccountListSchema,
  lotdgAdministrationMutationSchema,
  lotdgAdministrationSummarySchema,
  type LotdgAdministrationAccountList,
  type LotdgAdministrationSummary,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgAdministrationScreen({ characterId }: { readonly characterId: number }) {
  const { translate } = useLotdgLocale()
  const [summary, setSummary] = useState<LotdgAdministrationSummary | null>(null)
  const [accountList, setAccountList] = useState<LotdgAdministrationAccountList | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMON, path, valueMap)

  const reload = useCallback(() => {
    getJson(`/administration/${characterId}/summary`, lotdgAdministrationSummarySchema)
      .then(setSummary)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })

    getJson(
      `/administration/${characterId}/account-list?search_term=${encodeURIComponent(searchTerm)}`,
      lotdgAdministrationAccountListSchema,
    )
      .then(setAccountList)
      .catch(() => {
        setAccountList(null)
      })
  }, [characterId, searchTerm, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const mutate = async (action: string, body: Record<string, string | number>) => {
    try {
      await postForm(
        `/administration/${characterId}/${action}`,
        lotdgAdministrationMutationSchema,
        body,
      )
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('administration.title')}</h2>

      {summary !== null && (
        <p>
          {label('administration.summary', {
            account: summary.account_count,
            character: summary.character_count,
            creature: summary.creature_count,
            weapon: summary.weapon_count,
            armor: summary.armor_count,
            petition: summary.petition_count,
            ban: summary.ban_count,
          })}
        </p>
      )}

      <p>
        <input
          className="lotdg-input"
          value={searchTerm}
          placeholder={label('administration.search')}
          onChange={(event) => setSearchTerm(event.target.value)}
        />
      </p>

      <div className="lotdg-table-scroll">
        <table className="lotdg-table">
          <tbody>
            <tr>
              <th>{label('administration.column.login-name')}</th>
              <th>{label('administration.column.display-name')}</th>
              <th>{label('administration.column.level')}</th>
              <th>{label('administration.column.superuser-level')}</th>
              <th>{label('administration.column.action')}</th>
            </tr>
            {accountList?.account_list.map((account) => (
              <tr key={account.account_id}>
                <td>{account.login_name}</td>
                <td>{account.display_name}</td>
                <td>{account.level ?? '-'}</td>
                <td>
                  <select
                    className="lotdg-select"
                    value={account.superuser_level}
                    onChange={(event) =>
                      void mutate('account-level', {
                        target_account_id: account.account_id,
                        superuser_level: event.target.value,
                      })
                    }
                  >
                    {[0, 1, 2, 3].map((level) => (
                      <option key={level} value={level}>
                        {level}
                      </option>
                    ))}
                  </select>
                </td>
                <td>
                  <button
                    type="button"
                    className="lotdg-button"
                    onClick={() =>
                      void mutate('account-lock', {
                        target_account_id: account.account_id,
                        is_locked: account.is_locked ? '0' : '1',
                      })
                    }
                  >
                    {label(
                      account.is_locked
                        ? 'administration.action.unlock'
                        : 'administration.action.lock',
                    )}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {message !== '' && <p className="colLtRed">{message}</p>}
    </section>
  )
}
