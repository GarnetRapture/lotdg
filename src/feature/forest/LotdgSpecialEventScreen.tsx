import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgSpecialEventSchema,
  type LotdgSpecialEvent,
} from '../../shared/schema/world/lotdg-special-event-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_INSTANT_SPECIAL_EVENT_CODE_LIST,
  LOTDG_SPECIAL_EVENT_ACCEPT_ACTION_CODE,
  LOTDG_SPECIAL_EVENT_ACTION_CODE,
  LOTDG_SPECIAL_EVENT_CODE,
  LOTDG_SPECIAL_EVENT_DECLINE_ACTION_CODE,
  LOTDG_SPECIAL_EVENT_STAGE_CODE,
  type LotdgSpecialEventActionCode,
} from '../../shared/constant/lotdg-special-event-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_COMMENT_MAXIMUM_LENGTH } from '../../shared/constant/lotdg-commentary-section-code'
import type { LotdgSpecialEventScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgCommentLine,
  LotdgFieldRow,
  LotdgForm,
  LotdgMarkupText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_DECIMAL_RADIX = 10

const LOTDG_KITTEN_LABEL_SEPARATOR = ' / '

export function LotdgSpecialEventScreen({
  characterId,
  eventCode,
  onStateChange,
  onLeave,
}: LotdgSpecialEventScreenProps) {
  const { translate } = useLotdgLocale()
  const [event, setEvent] = useState<LotdgSpecialEvent | null>(null)
  const [betText, setBetText] = useState('')
  const [guessText, setGuessText] = useState('')
  const [answerText, setAnswerText] = useState('')
  const [commentText, setCommentText] = useState('')
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.FOREST, path, valueMap)

  const start = useCallback(() => {
    getJson(`/special/${eventCode}/${characterId}/start`, lotdgSpecialEventSchema)
      .then((result) => {
        setEvent(result)
        onStateChange()
      })
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, eventCode, onStateChange, translate])

  useEffect(() => {
    start()
  }, [start])

  const act = async (
    action: LotdgSpecialEventActionCode,
    body: Record<string, string | number> = {},
  ) => {
    try {
      const result = await postForm(
        `/special/${eventCode}/${characterId}/${action}`,
        lotdgSpecialEventSchema,
        body,
      )

      setEvent(result)
      setMessage(
        result.message_key === undefined
          ? ''
          : resolveMessageKeyLabel(result.message_key, translate),
      )
      onStateChange()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const submitBet = () => {
    void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.BET, {
      bet: Number.parseInt(betText, LOTDG_DECIMAL_RADIX) || 0,
    })
  }

  const submitGuess = () => {
    void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.GUESS, {
      guess: Number.parseInt(guessText, LOTDG_DECIMAL_RADIX) || 0,
    })
  }

  const submitAnswer = () => {
    void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.ANSWER, { answer: answerText })
  }

  const submitComment = () => {
    if (commentText.trim() === '') {
      return
    }

    void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.POST, { comment_text: commentText })
    setCommentText('')
  }

  const renderOutcome = () => {
    if (event === null || event.outcome === undefined) {
      return null
    }

    return (
      <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
        {label(`special.${eventCode}.outcome.${event.outcome}`, {
          gold: event.gold_gained ?? event.gold_lost ?? 0,
          gem: event.gem_gained ?? event.gem_lost ?? 0,
          charm: event.charm_gained ?? event.charm_lost ?? 0,
          turn: event.turn_gained ?? event.turn_lost ?? 0,
          experience: event.experience_gained ?? 0,
          favor: event.favor_gained ?? 0,
          hitPoint: event.hit_point ?? 0,
          secret: event.secret_number ?? 0,
          answer: event.answer_text ?? '',
          reward: event.reward ?? '',
        })}
      </LotdgText>
    )
  }

  const renderChoice = () => {
    if (event === null) {
      return null
    }

    if (LOTDG_INSTANT_SPECIAL_EVENT_CODE_LIST.includes(eventCode)) {
      return null
    }

    if (
      event.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.AWAITING_CHOICE ||
      event.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.OUTSIDE
    ) {
      if (eventCode === LOTDG_SPECIAL_EVENT_CODE.DISTRESS) {
        return (
          <LotdgActionRow>
            {(event.location_code_list ?? []).map((locationCode) => (
              <LotdgButton
                key={locationCode}
                labelSlot={label(`special.distress.location-${locationCode}`)}
                onSelect={() =>
                  void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.VISIT, { location_code: locationCode })
                }
              />
            ))}
            <LotdgButton
              labelSlot={label('special.action.decline')}
              onSelect={() => void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.IGNORE)}
            />
          </LotdgActionRow>
        )
      }

      return (
        <LotdgActionRow>
          <LotdgButton
            labelSlot={label('special.action.accept')}
            onSelect={() => void act(LOTDG_SPECIAL_EVENT_ACCEPT_ACTION_CODE[eventCode])}
          />
          <LotdgButton
            labelSlot={label('special.action.decline')}
            onSelect={() => void act(LOTDG_SPECIAL_EVENT_DECLINE_ACTION_CODE[eventCode])}
          />
        </LotdgActionRow>
      )
    }

    return null
  }

  return (
    <LotdgScreen titleText={label(`special.${eventCode}.title`)}>
      <LotdgMarkupText sourceText={label(`special.${eventCode}.description`)} />

      {renderChoice()}

      {event?.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.AWAITING_BET && (
        <LotdgForm onSubmit={submitBet}>
          <LotdgText>
            {label('special.oldmanbet.rule', {
              minimum: event.minimum_number ?? 1,
              maximum: event.maximum_number ?? 100,
              tryCount: event.maximum_try ?? 6,
              multiplier: event.win_multiplier ?? 3,
              gold: event.gold ?? 0,
            })}
          </LotdgText>
          <LotdgFieldRow>
            <LotdgTextField value={betText} onValueChange={setBetText} isNumeric />
            <LotdgSubmitButton labelSlot={label('special.action.bet')} />
          </LotdgFieldRow>
        </LotdgForm>
      )}

      {event?.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.AWAITING_GUESS && (
        <LotdgForm onSubmit={submitGuess}>
          <LotdgText>
            {label('special.oldmanbet.progress', {
              bet: event.bet ?? 0,
              tryCount: event.try_count ?? 0,
              remaining: event.remaining_try ?? 0,
            })}
            {event.hint !== undefined && ` ${label(`special.oldmanbet.hint.${event.hint}`)}`}
          </LotdgText>
          <LotdgFieldRow>
            <LotdgTextField value={guessText} onValueChange={setGuessText} isNumeric />
            <LotdgSubmitButton labelSlot={label('special.action.guess')} />
          </LotdgFieldRow>
        </LotdgForm>
      )}

      {event?.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.AWAITING_ANSWER && (
        <LotdgForm onSubmit={submitAnswer}>
          <LotdgMarkupText sourceText={event.riddle_text ?? ''} />
          <LotdgFieldRow>
            <LotdgTextField value={answerText} onValueChange={setAnswerText} />
            <LotdgSubmitButton labelSlot={label('special.action.answer')} />
          </LotdgFieldRow>
        </LotdgForm>
      )}

      {event?.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.AWAITING_GEM && (
        <LotdgActionRow>
          <LotdgButton
            labelSlot={label('special.necromancer.action.give-gem')}
            onSelect={() => void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.GIVE_GEM)}
          />
          <LotdgButton
            labelSlot={label('special.necromancer.action.keep-gem')}
            onSelect={() => void act(LOTDG_SPECIAL_EVENT_ACTION_CODE.KEEP_GEM)}
          />
        </LotdgActionRow>
      )}

      {event?.stage === LOTDG_SPECIAL_EVENT_STAGE_CODE.RESTING && (
        <>
          <LotdgText>
            {event.already_rested === true
              ? label('special.grassyfield.already-rested')
              : label('special.grassyfield.rested', {
                  mount: event.mount_name ?? '',
                  turn: event.turn_lost ?? 0,
                })}
          </LotdgText>

          {(event.comment_list ?? []).map((entry) => (
            <LotdgCommentLine
              key={entry.commentary_id}
              authorName={entry.display_name}
              commentText={entry.comment_text}
            />
          ))}

          <LotdgForm onSubmit={submitComment}>
            <LotdgFieldRow>
              <LotdgTextField
                value={commentText}
                onValueChange={setCommentText}
                maximumLength={LOTDG_COMMENT_MAXIMUM_LENGTH}
              />
              <LotdgSubmitButton labelSlot={label('special.action.post')} />
            </LotdgFieldRow>
          </LotdgForm>
        </>
      )}

      {event?.kitten_code_list !== undefined && (
        <LotdgText>
          {event.kitten_code_list
            .map((kittenCode) => label(`special.audrey.kitten-${kittenCode}`))
            .join(LOTDG_KITTEN_LABEL_SEPARATOR)}
        </LotdgText>
      )}

      {renderOutcome()}

      <LotdgNoticeLine messageText={message} />

      <LotdgActionRow>
        <LotdgButton labelSlot={label('special.action.return-to-forest')} onSelect={onLeave} />
      </LotdgActionRow>
    </LotdgScreen>
  )
}
