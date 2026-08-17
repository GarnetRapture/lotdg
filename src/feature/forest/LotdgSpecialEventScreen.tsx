import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgSpecialEventSchema,
  type LotdgSpecialEvent,
} from '../../shared/schema/world/lotdg-special-event-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_INSTANT_SPECIAL_EVENT_CODE_LIST,
  LOTDG_SPECIAL_EVENT_CODE,
  type LotdgSpecialEventCode,
} from '../../shared/constant/lotdg-special-event-code'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgSpecialEventScreen({
  characterId,
  eventCode,
  onStateChange,
  onLeave,
}: LotdgMutableScreenProps & {
  readonly eventCode: LotdgSpecialEventCode
  readonly onLeave: () => void
}) {
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

  const act = async (action: string, body: Record<string, string | number> = {}) => {
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

  const submitBet = (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()
    void act('bet', { bet: Number.parseInt(betText, 10) || 0 })
  }

  const submitGuess = (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()
    void act('guess', { guess: Number.parseInt(guessText, 10) || 0 })
  }

  const submitAnswer = (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()
    void act('answer', { answer: answerText })
  }

  const submitComment = (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()

    if (commentText.trim() === '') {
      return
    }

    void act('post', { comment_text: commentText })
    setCommentText('')
  }

  const renderOutcome = () => {
    if (event === null || event.outcome === undefined) {
      return null
    }

    return (
      <p className="colLtGreen">
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
      </p>
    )
  }

  const renderChoice = () => {
    if (event === null) {
      return null
    }

    if (LOTDG_INSTANT_SPECIAL_EVENT_CODE_LIST.includes(eventCode)) {
      return null
    }

    if (event.stage === 'awaiting-choice' || event.stage === 'outside') {
      const acceptAction =
        eventCode === LOTDG_SPECIAL_EVENT_CODE.GLOWING_STREAM
          ? 'drink'
          : eventCode === LOTDG_SPECIAL_EVENT_CODE.FAIRY ||
              eventCode === LOTDG_SPECIAL_EVENT_CODE.SKILL_MASTER
            ? 'give'
            : eventCode === LOTDG_SPECIAL_EVENT_CODE.RIDDLES
              ? 'accept'
              : eventCode === LOTDG_SPECIAL_EVENT_CODE.AUDREY
                ? 'play'
                : eventCode === LOTDG_SPECIAL_EVENT_CODE.GOLD_MINE
                  ? 'mine'
                  : eventCode === LOTDG_SPECIAL_EVENT_CODE.NECROMANCER
                    ? 'approach'
                    : eventCode === LOTDG_SPECIAL_EVENT_CODE.OLD_MAN_TOWN
                      ? 'escort'
                      : eventCode === LOTDG_SPECIAL_EVENT_CODE.DARK_HORSE
                        ? 'enter'
                        : 'start'

      const declineAction =
        eventCode === LOTDG_SPECIAL_EVENT_CODE.FAIRY ||
        eventCode === LOTDG_SPECIAL_EVENT_CODE.SKILL_MASTER
          ? 'refuse'
          : eventCode === LOTDG_SPECIAL_EVENT_CODE.AUDREY
            ? 'run'
            : eventCode === LOTDG_SPECIAL_EVENT_CODE.NECROMANCER ||
                eventCode === LOTDG_SPECIAL_EVENT_CODE.DARK_HORSE
              ? 'leave'
              : eventCode === LOTDG_SPECIAL_EVENT_CODE.DISTRESS
                ? 'ignore'
                : 'decline'

      if (eventCode === LOTDG_SPECIAL_EVENT_CODE.DISTRESS) {
        return (
          <p>
            {(event.location_code_list ?? []).map((locationCode) => (
              <button
                key={locationCode}
                type="button"
                className="lotdg-button"
                onClick={() => void act('visit', { location_code: locationCode })}
              >
                {label(`special.distress.location-${locationCode}`)}
              </button>
            ))}{' '}
            <button type="button" className="lotdg-button" onClick={() => void act('ignore')}>
              {label('special.action.decline')}
            </button>
          </p>
        )
      }

      return (
        <p>
          <button type="button" className="lotdg-button" onClick={() => void act(acceptAction)}>
            {label('special.action.accept')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={() => void act(declineAction)}>
            {label('special.action.decline')}
          </button>
        </p>
      )
    }

    return null
  }

  return (
    <section>
      <h2>{label(`special.${eventCode}.title`)}</h2>

      <p>{parseLegacyMarkup(label(`special.${eventCode}.description`))}</p>

      {renderChoice()}

      {event?.stage === 'awaiting-bet' && (
        <form onSubmit={submitBet}>
          <p>
            {label('special.old-man-bet.rule', {
              minimum: event.minimum_number ?? 1,
              maximum: event.maximum_number ?? 100,
              tryCount: event.maximum_try ?? 6,
              multiplier: event.win_multiplier ?? 3,
              gold: event.gold ?? 0,
            })}
          </p>
          <p>
            <input
              className="lotdg-input"
              inputMode="numeric"
              value={betText}
              onChange={(changeEvent) => setBetText(changeEvent.target.value)}
            />{' '}
            <button type="submit" className="lotdg-button">
              {label('special.action.bet')}
            </button>
          </p>
        </form>
      )}

      {event?.stage === 'awaiting-guess' && (
        <form onSubmit={submitGuess}>
          <p>
            {label('special.old-man-bet.progress', {
              bet: event.bet ?? 0,
              tryCount: event.try_count ?? 0,
              remaining: event.remaining_try ?? 0,
            })}
            {event.hint !== undefined && ` ${label(`special.old-man-bet.hint.${event.hint}`)}`}
          </p>
          <p>
            <input
              className="lotdg-input"
              inputMode="numeric"
              value={guessText}
              onChange={(changeEvent) => setGuessText(changeEvent.target.value)}
            />{' '}
            <button type="submit" className="lotdg-button">
              {label('special.action.guess')}
            </button>
          </p>
        </form>
      )}

      {event?.stage === 'awaiting-answer' && (
        <form onSubmit={submitAnswer}>
          <p>{parseLegacyMarkup(event.riddle_text ?? '')}</p>
          <p>
            <input
              className="lotdg-input"
              value={answerText}
              onChange={(changeEvent) => setAnswerText(changeEvent.target.value)}
            />{' '}
            <button type="submit" className="lotdg-button">
              {label('special.action.answer')}
            </button>
          </p>
        </form>
      )}

      {event?.stage === 'awaiting-gem' && (
        <p>
          <button type="button" className="lotdg-button" onClick={() => void act('give-gem')}>
            {label('special.necromancer.action.give-gem')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={() => void act('keep-gem')}>
            {label('special.necromancer.action.keep-gem')}
          </button>
        </p>
      )}

      {event?.stage === 'resting' && (
        <>
          <p>
            {event.already_rested === true
              ? label('special.grassyfield.already-rested')
              : label('special.grassyfield.rested', {
                  mount: event.mount_name ?? '',
                  turn: event.turn_lost ?? 0,
                })}
          </p>

          {(event.comment_list ?? []).map((entry) => (
            <p key={entry.commentary_id}>
              <span className="colLtWhite">{entry.display_name}</span>{' '}
              {parseLegacyMarkup(entry.comment_text)}
            </p>
          ))}

          <form onSubmit={submitComment}>
            <p>
              <input
                className="lotdg-input"
                value={commentText}
                maxLength={200}
                onChange={(changeEvent) => setCommentText(changeEvent.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('special.action.post')}
              </button>
            </p>
          </form>
        </>
      )}

      {event?.kitten_code_list !== undefined && (
        <p>
          {event.kitten_code_list
            .map((kittenCode) => label(`special.audrey.kitten-${kittenCode}`))
            .join(' / ')}
        </p>
      )}

      {renderOutcome()}

      <LotdgNoticeLine messageText={message} />

      <p>
        <button type="button" className="lotdg-button" onClick={onLeave}>
          {label('special.action.return-to-forest')}
        </button>
      </p>
    </section>
  )
}
