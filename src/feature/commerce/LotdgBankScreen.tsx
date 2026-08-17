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

export function LotdgBankScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
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
    <section>
      <h2>{label('bank.title')}</h2>

      {inspect !== null && (
        <p>
          {label('bank.balance', {
            gold: inspect.gold,
            goldInBank: inspect.gold_in_bank,
            depositLimit: inspect.deposit_limit,
            borrowLimit: inspect.borrow_limit,
          })}
        </p>
      )}

      <p>
        <label htmlFor="bank-amount">{label('bank.amount')}</label>{' '}
        <input
          id="bank-amount"
          className="lotdg-input"
          value={amount}
          onChange={(event) => setAmount(event.target.value)}
        />
      </p>

      <p>
        <button type="button" className="lotdg-button" onClick={() => void operate('deposit')}>
          {label('bank.action.deposit')}
        </button>{' '}
        <button type="button" className="lotdg-button" onClick={() => void operate('withdraw')}>
          {label('bank.action.withdraw')}
        </button>{' '}
        <button type="button" className="lotdg-button" onClick={() => void operate('borrow')}>
          {label('bank.action.borrow')}
        </button>
      </p>

      {inspect?.transfer_allowed === true && (
        <p>
          <label htmlFor="bank-recipient">{label('bank.recipient')}</label>{' '}
          <input
            id="bank-recipient"
            className="lotdg-input"
            value={recipientLoginName}
            onChange={(event) => setRecipientLoginName(event.target.value)}
          />{' '}
          <button
            type="button"
            className="lotdg-button"
            onClick={() => void operate('transfer', { recipient_login_name: recipientLoginName })}
          >
            {label('bank.action.transfer')}
          </button>
        </p>
      )}

      {message !== '' && <p className="colLtYellow">{message}</p>}
    </section>
  )
}
