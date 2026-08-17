import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgPetitionListSchema,
  lotdgPetitionMutationSchema,
  type LotdgPetitionList,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgFieldRow,
  LotdgForm,
  LotdgInlineText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextAreaField,
} from '../../shared/ui'

export function LotdgPetitionScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [petitionList, setPetitionList] = useState<LotdgPetitionList | null>(null)
  const [body, setBody] = useState('')
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const reload = useCallback(() => {
    getJson('/petition/list', lotdgPetitionListSchema)
      .then(setPetitionList)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [translate])

  useEffect(() => {
    reload()
  }, [reload])

  const submit = async () => {
    try {
      const result = await postForm('/petition/submit', lotdgPetitionMutationSchema, {
        character_id: characterId,
        body,
      })

      setMessage(
        result.submitted === true
          ? label('petition.submitted')
          : resolveMessageKeyLabel(result.message_key, translate),
      )
      setBody('')
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('petition.title')}>
      {petitionList !== null && (
        <LotdgText>
          {label('petition.summary', {
            unseen: petitionList.status_summary.unseen,
            seen: petitionList.status_summary.seen,
            closed: petitionList.status_summary.closed,
          })}
        </LotdgText>
      )}

      <LotdgForm onSubmit={() => void submit()}>
        <LotdgFieldRow isStacked>
          <LotdgTextAreaField value={body} onValueChange={setBody} />
          <LotdgSubmitButton labelSlot={label('petition.action.submit')} />
        </LotdgFieldRow>
      </LotdgForm>

      {petitionList?.petition_list.map((petition) => (
        <LotdgText key={petition.petition_id}>
          <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_WHITE}>
            {petition.display_name}
          </LotdgInlineText>{' '}
          {petition.body}
        </LotdgText>
      ))}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
