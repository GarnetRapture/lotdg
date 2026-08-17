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
import { LOTDG_SELECTABLE_SPECIALTY_CODE_LIST } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_COMMENTARY_SECTION_CODE } from '../../shared/constant/lotdg-commentary-section-code'
import { LOTDG_BOOLEAN_FIELD_VALUE } from '../../shared/constant/lotdg-form-token'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

const LOTDG_INN_ACTION_CODE = {
  ALE: 'ale',
  ROOM: 'room',
  SPECIALTY: 'specialty',
} as const

export function LotdgInnScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
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
    <LotdgScreen titleText={label('inn.title')}>
      {inn !== null && (
        <>
          <LotdgText>
            {label('inn.status', {
              gold: inn.gold,
              drunkenness: inn.drunkenness,
              alePrice: inn.ale_price,
              roomPrice: inn.room_price,
              roomPriceFromBank: inn.room_price_from_bank,
            })}
          </LotdgText>

          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('inn.action.ale')}
              isDisabled={!inn.can_drink}
              onSelect={() => void act(LOTDG_INN_ACTION_CODE.ALE)}
            />
            <LotdgButton
              labelSlot={label('inn.action.room')}
              onSelect={() =>
                void act(LOTDG_INN_ACTION_CODE.ROOM, {
                  pay_from_bank: LOTDG_BOOLEAN_FIELD_VALUE.FALSE,
                })
              }
            />
            <LotdgButton
              labelSlot={label('inn.action.room-from-bank')}
              onSelect={() =>
                void act(LOTDG_INN_ACTION_CODE.ROOM, {
                  pay_from_bank: LOTDG_BOOLEAN_FIELD_VALUE.TRUE,
                })
              }
            />
          </LotdgActionRow>

          <LotdgActionRow>
            {LOTDG_SELECTABLE_SPECIALTY_CODE_LIST.map((specialtyCode) => (
              <LotdgButton
                key={specialtyCode}
                labelSlot={label(`inn.action.specialty-${specialtyCode}`)}
                onSelect={() =>
                  void act(LOTDG_INN_ACTION_CODE.SPECIALTY, { specialty_code: specialtyCode })
                }
              />
            ))}
          </LotdgActionRow>
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard
        characterId={characterId}
        sectionCode={LOTDG_COMMENTARY_SECTION_CODE.INN}
      />
    </LotdgScreen>
  )
}
