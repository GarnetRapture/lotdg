import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgOuthouseActionSchema,
  lotdgOuthouseInspectSchema,
  type LotdgOuthouseInspect,
} from '../../shared/schema/world/lotdg-world-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TOILET_TYPE, type LotdgToiletType } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'

export function LotdgOuthouseScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [outhouse, setOuthouse] = useState<LotdgOuthouseInspect | null>(null)
  const [usedToiletType, setUsedToiletType] = useState<LotdgToiletType | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/outhouse/${characterId}/inspect`, lotdgOuthouseInspectSchema)
      .then(setOuthouse)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, path, valueMap)

  const enterStall = async (toiletType: LotdgToiletType) => {
    try {
      const result = await postForm(`/outhouse/${characterId}/use`, lotdgOuthouseActionSchema, {
        toilet_type: toiletType,
      })

      if (result.used !== true) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
        setUsedToiletType(null)
      } else {
        setMessage(label(`outhouse.used-${toiletType}`, { paid: result.paid ?? 0 }))
        setUsedToiletType(toiletType)
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const washHands = async () => {
    if (usedToiletType === null) {
      return
    }

    try {
      const result = await postForm(`/outhouse/${characterId}/wash`, lotdgOuthouseActionSchema, {
        toilet_type: usedToiletType,
      })

      setMessage(
        result.rewarded === true
          ? label('outhouse.wash-rewarded', {
              gold: result.gold_gained ?? 0,
              gem: result.gem_gained ?? 0,
            })
          : label('outhouse.wash-plain'),
      )

      setUsedToiletType(null)
      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const skipWashing = async () => {
    try {
      const result = await postForm(
        `/outhouse/${characterId}/skip-wash`,
        lotdgOuthouseActionSchema,
        {},
      )

      setMessage(
        result.punished === true
          ? label('outhouse.skip-punished', { gold: result.gold_lost ?? 0 })
          : label('outhouse.skip-plain'),
      )

      setUsedToiletType(null)
      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('outhouse.title')}>
      {outhouse !== null && (
        <>
          <LotdgText>{label('outhouse.description')}</LotdgText>

          {outhouse.used_today ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
              {label('outhouse.already-used')}
            </LotdgText>
          ) : (
            <LotdgActionRow>
              <LotdgButton
                labelSlot={label('outhouse.action.public')}
                onSelect={() => void enterStall(LOTDG_TOILET_TYPE.PUBLIC)}
              />
              <LotdgButton
                labelSlot={label('outhouse.action.private', { cost: outhouse.private_cost })}
                isDisabled={!outhouse.can_pay}
                onSelect={() => void enterStall(LOTDG_TOILET_TYPE.PRIVATE)}
              />
            </LotdgActionRow>
          )}

          {usedToiletType !== null && (
            <LotdgActionRow>
              <LotdgButton
                labelSlot={label('outhouse.action.wash')}
                onSelect={() => void washHands()}
              />
              <LotdgButton
                labelSlot={label('outhouse.action.skip-wash')}
                onSelect={() => void skipWashing()}
              />
            </LotdgActionRow>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
