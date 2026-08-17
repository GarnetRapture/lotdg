import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgInnActionSchema,
  lotdgInnEnterSchema,
  type LotdgInnEnter,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgInnScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
  const { translate } = useLotdgLocale()
  const [inn, setInn] = useState<LotdgInnEnter | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/inn/${characterId}/enter`, lotdgInnEnterSchema)
      .then(setInn)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.VILLAGE, path, valueMap)

  const act = async (action: string, body: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(`/inn/${characterId}/${action}`, lotdgInnActionSchema, body)

      if (result.message_key !== undefined) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
      } else if (result.bought === true) {
        setMessage(
          label('inn.ale-bought', {
            healed: result.healed_hit_point ?? 0,
            turn: result.gained_turn ?? 0,
          }),
        )
      } else if (result.rented === true) {
        setMessage(label('inn.room-rented', { price: result.price ?? 0 }))
      } else if (result.changed === true) {
        setMessage(label('inn.specialty-changed'))
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('inn.title')}</h2>

      {inn !== null && (
        <>
          <p>
            {label('inn.status', {
              gold: inn.gold,
              drunkenness: inn.drunkenness,
              alePrice: inn.ale_price,
              roomPrice: inn.room_price,
              roomPriceFromBank: inn.room_price_from_bank,
            })}
          </p>

          <p>
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('ale')}
              disabled={!inn.can_drink}
            >
              {label('inn.action.ale')}
            </button>{' '}
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('room', { pay_from_bank: '0' })}
            >
              {label('inn.action.room')}
            </button>{' '}
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('room', { pay_from_bank: '1' })}
            >
              {label('inn.action.room-from-bank')}
            </button>
          </p>

          <p>
            {[1, 2, 3].map((specialtyCode) => (
              <button
                key={specialtyCode}
                type="button"
                className="lotdg-button"
                onClick={() => void act('specialty', { specialty_code: specialtyCode })}
              >
                {label(`inn.action.specialty-${specialtyCode}`)}
              </button>
            ))}
          </p>
        </>
      )}

      {message !== '' && <p className="colLtYellow">{message}</p>}

      <LotdgCommentaryBoard characterId={characterId} sectionCode="inn" />
    </section>
  )
}
