import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgCommentaryListSchema,
  lotdgCommentaryPostSchema,
  type LotdgCommentaryList,
} from '../../shared/schema/social/lotdg-commentary-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgCommentaryBoard({
  characterId,
  sectionCode,
}: {
  readonly characterId: number
  readonly sectionCode: string
}) {
  const { translate } = useLotdgLocale()
  const [board, setBoard] = useState<LotdgCommentaryList | null>(null)
  const [commentText, setCommentText] = useState('')
  const [errorMessage, setErrorMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/commentary/${sectionCode}/${characterId}/list`, lotdgCommentaryListSchema)
      .then(setBoard)
      .catch((error: unknown) => {
        setErrorMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, sectionCode, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (commentText.trim() === '') {
      return
    }

    try {
      const result = await postForm(
        `/commentary/${sectionCode}/${characterId}/post`,
        lotdgCommentaryPostSchema,
        { comment_text: commentText },
      )

      if (!result.posted) {
        setErrorMessage(resolveMessageKeyLabel(result.message_key, translate))

        return
      }

      setCommentText('')
      setErrorMessage('')
      reload()
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h3>{translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, 'commentary.title')}</h3>

      {board?.comment_list.map((comment) => (
        <p key={comment.commentary_id}>
          <span className="colLtWhite">{comment.display_name}</span>{' '}
          {parseLegacyMarkup(comment.comment_text)}
        </p>
      ))}

      {errorMessage !== '' && <p className="colLtRed">{errorMessage}</p>}

      <form onSubmit={handleSubmit}>
        <input
          className="lotdg-input"
          value={commentText}
          maxLength={200}
          onChange={(event) => setCommentText(event.target.value)}
        />{' '}
        <button type="submit" className="lotdg-button">
          {translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, 'commentary.post')}
        </button>
      </form>
    </section>
  )
}
