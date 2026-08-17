import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgCommentaryListSchema,
  lotdgCommentaryPostSchema,
  type LotdgCommentaryList,
} from '../../shared/schema/social/lotdg-commentary-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_COMMENT_MAXIMUM_LENGTH } from '../../shared/constant/lotdg-commentary-section-code'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import type { LotdgCommentaryBoardProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgCommentLine,
  LotdgFieldRow,
  LotdgForm,
  LotdgNoticeLine,
  LotdgSection,
  LotdgSubmitButton,
  LotdgTextField,
} from '../../shared/ui'

export function LotdgCommentaryBoard({ characterId, sectionCode }: LotdgCommentaryBoardProps) {
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

  const handleSubmit = async () => {
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
    <LotdgSection titleSlot={translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, 'commentary.title')}>
      {board?.comment_list.map((comment) => (
        <LotdgCommentLine
          key={comment.commentary_id}
          authorName={comment.display_name}
          commentText={comment.comment_text}
        />
      ))}

      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />

      <LotdgForm onSubmit={() => void handleSubmit()}>
        <LotdgFieldRow>
          <LotdgTextField
            value={commentText}
            maximumLength={LOTDG_COMMENT_MAXIMUM_LENGTH}
            onValueChange={setCommentText}
          />
          <LotdgSubmitButton
            labelSlot={translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, 'commentary.post')}
          />
        </LotdgFieldRow>
      </LotdgForm>
    </LotdgSection>
  )
}
