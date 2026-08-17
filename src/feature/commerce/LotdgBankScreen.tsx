import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgBankInspectSchema,
  lotdgBankOperationSchema,
  type LotdgBankInspect,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgFieldRow,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_BANK_ACTION_CODE = {
  DEPOSIT: 'deposit',
  WITHDRAW: 'withdraw',
  BORROW: 'borrow',
  TRANSFER: 'transfer',
} as const

export function LotdgBankScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [inspect, setInspect] = useState<LotdgBankInspect | null>(null)
  const [amount, setAmount] = useState('0')
  const [recipientLoginName, setRecipientLoginName] = useState('')
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/bank/${characterId}/inspect`, lotdgBankInspectSchema)
      .then(setInspect)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMERCE, path, valueMap)

  const operate = async (action: string, extraBody: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(`/bank/${characterId}/${action}`, lotdgBankOperationSchema, {
        amount: Number(amount) || 0,
        ...extraBody,
      })

      setMessage(
        result.succeeded
          ? label('bank.success')
          : resolveMessageKeyLabel(result.message_key, translate),
      )

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('bank.title')}>
      {inspect !== null && (
        <LotdgText>
          {label('bank.balance', {
            gold: inspect.gold,
            goldInBank: inspect.gold_in_bank,
            depositLimit: inspect.deposit_limit,
            borrowLimit: inspect.borrow_limit,
          })}
        </LotdgText>
      )}

      <LotdgFieldRow>
        <LotdgTextField labelText={label('bank.amount')} value={amount} onValueChange={setAmount} />
      </LotdgFieldRow>

      <LotdgActionRow>
        <LotdgButton
          labelSlot={label('bank.action.deposit')}
          onSelect={() => void operate(LOTDG_BANK_ACTION_CODE.DEPOSIT)}
        />
        <LotdgButton
          labelSlot={label('bank.action.withdraw')}
          onSelect={() => void operate(LOTDG_BANK_ACTION_CODE.WITHDRAW)}
        />
        <LotdgButton
          labelSlot={label('bank.action.borrow')}
          onSelect={() => void operate(LOTDG_BANK_ACTION_CODE.BORROW)}
        />
      </LotdgActionRow>

      {inspect?.transfer_allowed === true && (
        <LotdgFieldRow>
          <LotdgTextField
            labelText={label('bank.recipient')}
            value={recipientLoginName}
            onValueChange={setRecipientLoginName}
          />
          <LotdgButton
            labelSlot={label('bank.action.transfer')}
            onSelect={() =>
              void operate(LOTDG_BANK_ACTION_CODE.TRANSFER, {
                recipient_login_name: recipientLoginName,
              })
            }
          />
        </LotdgFieldRow>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
