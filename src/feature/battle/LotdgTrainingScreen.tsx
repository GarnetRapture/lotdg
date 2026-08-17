import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgTrainingChallengeSchema,
  lotdgTrainingInspectSchema,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgActionRow, LotdgButton, LotdgScreen, LotdgSection, LotdgText } from '../../shared/ui'
import type { z } from 'zod'

type TrainingInspect = z.infer<typeof lotdgTrainingInspectSchema>
type TrainingChallenge = z.infer<typeof lotdgTrainingChallengeSchema>

export function LotdgTrainingScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
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
    <LotdgScreen titleText={label('training.title')}>
      {errorMessage !== '' && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>{errorMessage}</LotdgText>
      )}

      {inspect?.has_master === false && <LotdgText>{label('training.no-master')}</LotdgText>}

      {inspect?.has_master === true && (
        <>
          <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
            {label('training.master', {
              name: inspect.master_name ?? '',
              weapon: inspect.master_weapon_name ?? '',
            })}
          </LotdgText>
          <LotdgText>
            {label('training.experience', {
              current: inspect.current_experience ?? 0,
              required: inspect.required_experience ?? 0,
              missing: inspect.missing_experience ?? 0,
            })}
          </LotdgText>
          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('training.action.challenge')}
              isDisabled={inspect.can_challenge !== true}
              onSelect={() => void doChallenge()}
            />
          </LotdgActionRow>
        </>
      )}

      {challenge !== null && (
        <LotdgSection>
          {challenge.challenged === false && challenge.message_key !== undefined && (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
              {resolveMessageKeyLabel(challenge.message_key, translate)}
            </LotdgText>
          )}
          {challenge.victory === true && (
            <>
              <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
                {label('training.victory', { name: challenge.master_name ?? '' })}
              </LotdgText>
              <LotdgText>{challenge.master_message}</LotdgText>
              <LotdgText>
                {label('training.advancement', {
                  level: challenge.advancement?.level ?? 0,
                  maxHitPoint: challenge.advancement?.max_hit_point ?? 0,
                })}
              </LotdgText>
            </>
          )}
          {challenge.victory === false && challenge.challenged === true && (
            <>
              <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
                {label('training.defeat', { name: challenge.master_name ?? '' })}
              </LotdgText>
              <LotdgText>{challenge.master_message}</LotdgText>
            </>
          )}
        </LotdgSection>
      )}
    </LotdgScreen>
  )
}
