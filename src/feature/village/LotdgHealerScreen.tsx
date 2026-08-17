import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgHealerInspectSchema,
  lotdgHealerPurchaseSchema,
  type LotdgHealerInspect,
} from '../../shared/schema/world/lotdg-world-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgHealerScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [healer, setHealer] = useState<LotdgHealerInspect | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/healer/${characterId}/inspect`, lotdgHealerInspectSchema)
      .then(setHealer)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, path, valueMap)

  const buyPotion = async (percent: number) => {
    try {
      const result = await postForm(`/healer/${characterId}/buy`, lotdgHealerPurchaseSchema, {
        percent,
      })

      setMessage(
        result.healed
          ? label('healer.healed', {
              percent: result.percent ?? percent,
              price: result.price ?? 0,
              hitPoint: result.healed_hit_point ?? 0,
            })
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
      <h2>{label(healer?.is_golinda === true ? 'healer.title-golinda' : 'healer.title')}</h2>

      {healer !== null && (
        <>
          <p>
            {label('healer.status', {
              hitPoint: healer.hit_point,
              maxHitPoint: healer.max_hit_point,
              gold: healer.gold,
            })}
          </p>

          {healer.is_golinda && <p className="colLtGreen">{label('healer.golinda-discount')}</p>}

          {healer.needs_healing ? (
            <LotdgDataTable
              rowList={healer.price_list}
              rowKey={(option) => option.percent}
              columnList={[
                {
                  columnKey: 'percent',
                  headText: label('healer.column.percent'),
                  render: (option) => `${option.percent}%`,
                },
                {
                  columnKey: 'price',
                  headText: label('healer.column.price'),
                  render: (option) => option.price,
                },
                {
                  columnKey: 'action',
                  headText: label('healer.column.action'),
                  render: (option) => (
                    <button
                      type="button"
                      className="lotdg-button"
                      disabled={healer.gold < option.price}
                      onClick={() => void buyPotion(option.percent)}
                    >
                      {label('healer.action.buy')}
                    </button>
                  ),
                },
              ]}
            />
          ) : (
            <p className="colLtGreen">{label('healer.already-full')}</p>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard characterId={characterId} sectionCode="healer" />
    </section>
  )
}
