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
import { LOTDG_COMMENTARY_SECTION_CODE } from '../../shared/constant/lotdg-commentary-section-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgDataTable,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

const LOTDG_GEM_TRADER_ACTION_CODE = {
  BUY: 'buy',
  SELL: 'sell',
} as const

const LOTDG_GEM_SELL_MINIMUM_COUNT = 1

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
    <LotdgScreen titleText={label('gem-trader.title')}>
      {trader !== null && (
        <>
          <LotdgText>
            {label('gem-trader.status', { gold: trader.gold, gem: trader.gem })}
          </LotdgText>

          {trader.available ? (
            <>
              <LotdgText>{label('gem-trader.stock', { stock: trader.stock })}</LotdgText>

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
                      <LotdgButton
                        labelSlot={label('gem-trader.action.buy')}
                        isDisabled={!option.available}
                        onSelect={() =>
                          void act(LOTDG_GEM_TRADER_ACTION_CODE.BUY, {
                            option_code: option.option_code,
                          })
                        }
                      />
                    ),
                  },
                ]}
              />

              <LotdgActionRow>
                <LotdgText>
                  {label('gem-trader.sell-offer', { price: trader.sell_price_per_gem })}
                </LotdgText>
                <LotdgButton
                  labelSlot={label('gem-trader.action.sell')}
                  isDisabled={trader.gem < LOTDG_GEM_SELL_MINIMUM_COUNT}
                  onSelect={() => void act(LOTDG_GEM_TRADER_ACTION_CODE.SELL)}
                />
              </LotdgActionRow>
            </>
          ) : (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {label('gem-trader.unavailable')}
            </LotdgText>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard
        characterId={characterId}
        sectionCode={LOTDG_COMMENTARY_SECTION_CODE.GEM_TRADER}
      />
    </LotdgScreen>
  )
}
