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
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_COMMENTARY_SECTION_CODE } from '../../shared/constant/lotdg-commentary-section-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgButton,
  LotdgDataTable,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'
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
    <LotdgScreen
      titleText={label(healer?.is_golinda === true ? 'healer.title-golinda' : 'healer.title')}
    >
      {healer !== null && (
        <>
          <LotdgText>
            {label('healer.status', {
              hitPoint: healer.hit_point,
              maxHitPoint: healer.max_hit_point,
              gold: healer.gold,
            })}
          </LotdgText>

          {healer.is_golinda && (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
              {label('healer.golinda-discount')}
            </LotdgText>
          )}

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
                    <LotdgButton
                      labelSlot={label('healer.action.buy')}
                      isDisabled={healer.gold < option.price}
                      onSelect={() => void buyPotion(option.percent)}
                    />
                  ),
                },
              ]}
            />
          ) : (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
              {label('healer.already-full')}
            </LotdgText>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard
        characterId={characterId}
        sectionCode={LOTDG_COMMENTARY_SECTION_CODE.HEALER}
      />
    </LotdgScreen>
  )
}
