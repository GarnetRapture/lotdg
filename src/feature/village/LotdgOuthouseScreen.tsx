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
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

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

  const useToilet = async (toiletType: LotdgToiletType) => {
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
    <section>
      <h2>{label('outhouse.title')}</h2>

      {outhouse !== null && (
        <>
          <p>{label('outhouse.description')}</p>

          {outhouse.used_today ? (
            <p className="colLtYellow">{label('outhouse.already-used')}</p>
          ) : (
            <p>
              <button
                type="button"
                className="lotdg-button"
                onClick={() => void enterStall(LOTDG_TOILET_TYPE.PUBLIC)}
              >
                {label('outhouse.action.public')}
              </button>{' '}
              <button
                type="button"
                className="lotdg-button"
                disabled={!outhouse.can_pay}
                onClick={() => void enterStall(LOTDG_TOILET_TYPE.PRIVATE)}
              >
                {label('outhouse.action.private', { cost: outhouse.private_cost })}
              </button>
            </p>
          )}

          {usedToiletType !== null && (
            <p>
              <button type="button" className="lotdg-button" onClick={() => void washHands()}>
                {label('outhouse.action.wash')}
              </button>{' '}
              <button type="button" className="lotdg-button" onClick={() => void skipWashing()}>
                {label('outhouse.action.skip-wash')}
              </button>
            </p>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
