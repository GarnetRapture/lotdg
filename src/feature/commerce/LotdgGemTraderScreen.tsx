import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgGemTraderInspectSchema,
  lotdgGemTraderMutationSchema,
  type LotdgGemTraderInspect,
} from '../../shared/schema/world/lotdg-world-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgGemTraderScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [trader, setTrader] = useState<LotdgGemTraderInspect | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/gem-trader/${characterId}/inspect`, lotdgGemTraderInspectSchema)
      .then(setTrader)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMERCE, path, valueMap)

  const act = async (action: string, body: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(
        `/gem-trader/${characterId}/${action}`,
        lotdgGemTraderMutationSchema,
        body,
      )

      if (result.message_key !== undefined) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
      } else if (result.bought === true) {
        setMessage(label('gem-trader.bought', { gem: result.gem ?? 0, gold: result.gold ?? 0 }))
      } else if (result.sold === true) {
        setMessage(label('gem-trader.sold', { gold: result.gold ?? 0 }))
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('gem-trader.title')}</h2>

      {trader !== null && (
        <>
          <p>{label('gem-trader.status', { gold: trader.gold, gem: trader.gem })}</p>

          {trader.available ? (
            <>
              <p>{label('gem-trader.stock', { stock: trader.stock })}</p>

              <LotdgDataTable
                rowList={trader.purchase_option_list}
                rowKey={(option) => option.option_code}
                columnList={[
                  {
                    columnKey: 'gem',
                    headText: label('gem-trader.column.gem'),
                    render: (option) => option.gem,
                  },
                  {
                    columnKey: 'gold',
                    headText: label('gem-trader.column.gold'),
                    render: (option) => option.gold,
                  },
                  {
                    columnKey: 'action',
                    headText: label('gem-trader.column.action'),
                    render: (option) => (
                      <button
                        type="button"
                        className="lotdg-button"
                        disabled={!option.available}
                        onClick={() => void act('buy', { option_code: option.option_code })}
                      >
                        {label('gem-trader.action.buy')}
                      </button>
                    ),
                  },
                ]}
              />

              <p>
                {label('gem-trader.sell-offer', { price: trader.sell_price_per_gem })}{' '}
                <button
                  type="button"
                  className="lotdg-button"
                  disabled={trader.gem < 1}
                  onClick={() => void act('sell')}
                >
                  {label('gem-trader.action.sell')}
                </button>
              </p>
            </>
          ) : (
            <p className="colLtRed">{label('gem-trader.unavailable')}</p>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard characterId={characterId} sectionCode="gemtrader" />
    </section>
  )
}
