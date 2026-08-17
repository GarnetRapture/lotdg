import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgPetitionListSchema,
  lotdgPetitionMutationSchema,
  type LotdgPetitionList,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgPetitionScreen({ characterId }: { readonly characterId: number }) {
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

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

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
    <section>
      <h2>{label('petition.title')}</h2>

      {petitionList !== null && (
        <p>
          {label('petition.summary', {
            unseen: petitionList.status_summary.unseen,
            seen: petitionList.status_summary.seen,
            closed: petitionList.status_summary.closed,
          })}
        </p>
      )}

      <form onSubmit={submit}>
        <p>
          <textarea
            className="lotdg-input"
            rows={4}
            value={body}
            onChange={(event) => setBody(event.target.value)}
          />
        </p>
        <button type="submit" className="lotdg-button">
          {label('petition.action.submit')}
        </button>
      </form>

      {petitionList?.petition_list.map((petition) => (
        <p key={petition.petition_id}>
          <span className="colLtWhite">{petition.display_name}</span> {petition.body}
        </p>
      ))}

      {message !== '' && <p className="colLtYellow">{message}</p>}
    </section>
  )
}
