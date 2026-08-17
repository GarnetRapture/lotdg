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
import { LOTDG_SUPERUSER_LEVEL_LIST } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_BOOLEAN_FIELD_VALUE } from '../../shared/constant/lotdg-form-token'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgButton,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSelectField,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_ADMINISTRATION_ACTION_CODE = {
  ACCOUNT_LEVEL: 'account-level',
  ACCOUNT_LOCK: 'account-lock',
} as const

const LOTDG_MISSING_LEVEL_TEXT = '-'

export function LotdgAdministrationScreen({ characterId }: LotdgCharacterScreenProps) {
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
    <LotdgScreen titleText={label('administration.title')}>
      {summary !== null && (
        <LotdgText>
          {label('administration.summary', {
            account: summary.account_count,
            character: summary.character_count,
            creature: summary.creature_count,
            weapon: summary.weapon_count,
            armor: summary.armor_count,
            petition: summary.petition_count,
            ban: summary.ban_count,
          })}
        </LotdgText>
      )}

      <LotdgFieldRow>
        <LotdgTextField
          value={searchTerm}
          onValueChange={setSearchTerm}
          placeholderText={label('administration.search')}
        />
      </LotdgFieldRow>

      <LotdgDataTable
        rowList={accountList?.account_list ?? []}
        rowKey={(account) => account.account_id}
        columnList={[
          {
            columnKey: 'login-name',
            headText: label('administration.column.login-name'),
            render: (account) => account.login_name,
          },
          {
            columnKey: 'display-name',
            headText: label('administration.column.display-name'),
            render: (account) => account.display_name,
          },
          {
            columnKey: 'level',
            headText: label('administration.column.level'),
            render: (account) => account.level ?? LOTDG_MISSING_LEVEL_TEXT,
          },
          {
            columnKey: 'superuser-level',
            headText: label('administration.column.superuser-level'),
            render: (account) => (
              <LotdgSelectField
                value={String(account.superuser_level)}
                optionList={LOTDG_SUPERUSER_LEVEL_LIST.map((level) => ({
                  optionValue: String(level),
                  labelText: String(level),
                }))}
                onValueChange={(nextValue) =>
                  void mutate(LOTDG_ADMINISTRATION_ACTION_CODE.ACCOUNT_LEVEL, {
                    target_account_id: account.account_id,
                    superuser_level: nextValue,
                  })
                }
              />
            ),
          },
          {
            columnKey: 'action',
            headText: label('administration.column.action'),
            render: (account) => (
              <LotdgButton
                labelSlot={label(
                  account.is_locked ? 'administration.action.unlock' : 'administration.action.lock',
                )}
                onSelect={() =>
                  void mutate(LOTDG_ADMINISTRATION_ACTION_CODE.ACCOUNT_LOCK, {
                    target_account_id: account.account_id,
                    is_locked: account.is_locked
                      ? LOTDG_BOOLEAN_FIELD_VALUE.FALSE
                      : LOTDG_BOOLEAN_FIELD_VALUE.TRUE,
                  })
                }
              />
            ),
          },
        ]}
      />

      <LotdgNoticeLine messageText={message} tone={LOTDG_NOTICE_TONE.FAILURE} />
    </LotdgScreen>
  )
}
