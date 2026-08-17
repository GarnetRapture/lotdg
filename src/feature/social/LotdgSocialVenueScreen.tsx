import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgSocialVenueEnterSchema,
  type LotdgSocialVenueEnter,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { lotdgCommentaryPostSchema } from '../../shared/schema/social/lotdg-commentary-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_COMMENT_MAXIMUM_LENGTH } from '../../shared/constant/lotdg-commentary-section-code'
import type { LotdgSocialVenueScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgCommentLine,
  LotdgFieldRow,
  LotdgForm,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

export function LotdgSocialVenueScreen({ characterId, venueCode }: LotdgSocialVenueScreenProps) {
  const { translate } = useLotdgLocale()
  const [venue, setVenue] = useState<LotdgSocialVenueEnter | null>(null)
  const [commentText, setCommentText] = useState('')
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/venue/${venueCode}/${characterId}/enter`, lotdgSocialVenueEnterSchema)
      .then(setVenue)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, venueCode, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const post = async () => {
    if (commentText.trim() === '') {
      return
    }

    try {
      const result = await postForm(
        `/venue/${venueCode}/${characterId}/post`,
        lotdgCommentaryPostSchema,
        { comment_text: commentText },
      )

      if (!result.posted) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))

        return
      }

      setCommentText('')
      setMessage('')
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label(`venue.${venueCode}.title`)}>
      <LotdgText>{label(`venue.${venueCode}.description`)}</LotdgText>

      {venue !== null && venue.entered && (
        <>
          {(venue.comment_list ?? []).length === 0 ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {label('venue.empty')}
            </LotdgText>
          ) : (
            (venue.comment_list ?? []).map((entry) => (
              <LotdgCommentLine
                key={entry.commentary_id}
                authorName={entry.display_name}
                commentText={entry.comment_text}
              />
            ))
          )}

          <LotdgForm onSubmit={() => void post()}>
            <LotdgFieldRow>
              <LotdgTextField
                value={commentText}
                maximumLength={LOTDG_COMMENT_MAXIMUM_LENGTH}
                onValueChange={setCommentText}
              />
              <LotdgSubmitButton labelSlot={label('venue.action.post')} />
            </LotdgFieldRow>
          </LotdgForm>
        </>
      )}

      {venue !== null && !venue.entered && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
          {resolveMessageKeyLabel(venue.message_key, translate)}
        </LotdgText>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
