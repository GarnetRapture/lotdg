import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgSocialVenueEnterSchema,
  type LotdgSocialVenueEnter,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { lotdgCommentaryPostSchema } from '../../shared/schema/social/lotdg-commentary-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import type { LotdgSocialVenueCode } from '../../shared/constant/lotdg-legacy-code'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgSocialVenueScreen({
  characterId,
  venueCode,
}: LotdgCharacterScreenProps & { readonly venueCode: LotdgSocialVenueCode }) {
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

  const post = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

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
    <section>
      <h2>{label(`venue.${venueCode}.title`)}</h2>

      <p>{label(`venue.${venueCode}.description`)}</p>

      {venue !== null && venue.entered && (
        <>
          {(venue.comment_list ?? []).length === 0 ? (
            <p className="colDkWhite">{label('venue.empty')}</p>
          ) : (
            (venue.comment_list ?? []).map((entry) => (
              <p key={entry.commentary_id}>
                <span className="colLtWhite">{entry.display_name}</span>{' '}
                {parseLegacyMarkup(entry.comment_text)}
              </p>
            ))
          )}

          <form onSubmit={(event) => void post(event)}>
            <p>
              <input
                className="lotdg-input"
                value={commentText}
                maxLength={200}
                onChange={(event) => setCommentText(event.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('venue.action.post')}
              </button>
            </p>
          </form>
        </>
      )}

      {venue !== null && !venue.entered && (
        <p className="colLtRed">{resolveMessageKeyLabel(venue.message_key, translate)}</p>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
