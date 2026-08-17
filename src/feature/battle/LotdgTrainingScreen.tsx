import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgTrainingChallengeSchema,
  lotdgTrainingInspectSchema,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type { z } from 'zod'

type TrainingInspect = z.infer<typeof lotdgTrainingInspectSchema>
type TrainingChallenge = z.infer<typeof lotdgTrainingChallengeSchema>

export function LotdgTrainingScreen({
  characterId,
  onStateChange,
}: {
  readonly characterId: number
  readonly onStateChange: () => void
}) {
  const { translate } = useLotdgLocale()
  const [inspect, setInspect] = useState<TrainingInspect | null>(null)
  const [challenge, setChallenge] = useState<TrainingChallenge | null>(null)
  const [errorMessage, setErrorMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/training/${characterId}/inspect`, lotdgTrainingInspectSchema)
      .then(setInspect)
      .catch((error: unknown) => {
        setErrorMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.BATTLE, path, valueMap)

  const doChallenge = async () => {
    try {
      const result = await postForm(
        `/training/${characterId}/challenge`,
        lotdgTrainingChallengeSchema,
      )
      setChallenge(result)
      onStateChange()
      reload()
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('training.title')}</h2>

      {errorMessage !== '' && <p className="colLtRed">{errorMessage}</p>}

      {inspect?.has_master === false && <p>{label('training.no-master')}</p>}

      {inspect?.has_master === true && (
        <>
          <p className="colLtYellow">
            {label('training.master', {
              name: inspect.master_name ?? '',
              weapon: inspect.master_weapon_name ?? '',
            })}
          </p>
          <p>
            {label('training.experience', {
              current: inspect.current_experience ?? 0,
              required: inspect.required_experience ?? 0,
              missing: inspect.missing_experience ?? 0,
            })}
          </p>
          <button
            type="button"
            className="lotdg-button"
            onClick={() => void doChallenge()}
            disabled={inspect.can_challenge !== true}
          >
            {label('training.action.challenge')}
          </button>
        </>
      )}

      {challenge !== null && (
        <div>
          {challenge.challenged === false && challenge.message_key !== undefined && (
            <p className="colLtRed">{resolveMessageKeyLabel(challenge.message_key, translate)}</p>
          )}
          {challenge.victory === true && (
            <>
              <p className="colLtGreen">
                {label('training.victory', { name: challenge.master_name ?? '' })}
              </p>
              <p>{challenge.master_message}</p>
              <p>
                {label('training.advancement', {
                  level: challenge.advancement?.level ?? 0,
                  maxHitPoint: challenge.advancement?.max_hit_point ?? 0,
                })}
              </p>
            </>
          )}
          {challenge.victory === false && challenge.challenged === true && (
            <>
              <p className="colLtRed">
                {label('training.defeat', { name: challenge.master_name ?? '' })}
              </p>
              <p>{challenge.master_message}</p>
            </>
          )}
        </div>
      )}
    </section>
  )
}
