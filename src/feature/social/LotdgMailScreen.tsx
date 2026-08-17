import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgMailInboxSchema,
  lotdgMailMutationSchema,
  lotdgMailReadSchema,
  lotdgMailRecipientSearchSchema,
  lotdgMailReplySchema,
  type LotdgMailInbox,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import type { z } from 'zod'

type MailRead = z.infer<typeof lotdgMailReadSchema>
type MailRecipientSearch = z.infer<typeof lotdgMailRecipientSearchSchema>

export function LotdgMailScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [inbox, setInbox] = useState<LotdgMailInbox | null>(null)
  const [opened, setOpened] = useState<MailRead | null>(null)
  const [openedId, setOpenedId] = useState(0)
  const [checkedIdList, setCheckedIdList] = useState<readonly number[]>([])
  const [recipientSearch, setRecipientSearch] = useState<MailRecipientSearch | null>(null)
  const [recipientSearchTerm, setRecipientSearchTerm] = useState('')
  const [recipientLoginName, setRecipientLoginName] = useState('')
  const [subject, setSubject] = useState('')
  const [body, setBody] = useState('')
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/mail/${characterId}/inbox`, lotdgMailInboxSchema)
      .then(setInbox)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const open = async (mailMessageId: number) => {
    try {
      const result = await postForm(`/mail/${characterId}/read`, lotdgMailReadSchema, {
        mail_message_id: mailMessageId,
      })
      setOpened(result)
      setOpenedId(mailMessageId)
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const remove = async (mailMessageId: number) => {
    try {
      await postForm(`/mail/${characterId}/delete`, lotdgMailMutationSchema, {
        mail_message_id: mailMessageId,
      })
      setOpened(null)
      setOpenedId(0)
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const removeChecked = async () => {
    try {
      const result = await postForm(
        `/mail/${characterId}/delete-many`,
        lotdgMailMutationSchema,
        Object.fromEntries(
          checkedIdList.map((identifier, index) => [
            `mail_message_id_list[${index}]`,
            identifier,
          ]),
        ),
      )

      setMessage(
        result.message_key === undefined
          ? label('mail.deleted-many', { count: result.deleted_count ?? 0 })
          : resolveMessageKeyLabel(result.message_key, translate),
      )
      setCheckedIdList([])
      setOpened(null)
      setOpenedId(0)
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const prepareReply = async () => {
    if (openedId <= 0) {
      return
    }

    try {
      const result = await postForm(`/mail/${characterId}/reply`, lotdgMailReplySchema, {
        mail_message_id: openedId,
      })

      if (!result.prepared) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))

        return
      }

      setRecipientLoginName(result.recipient_login_name ?? '')
      setSubject(result.subject ?? '')
      setBody(`\n\n--- ${label('mail.quoted')} ---\n${result.quoted_body ?? ''}`)
      setMessage('')
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const searchRecipient = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (recipientSearchTerm.trim() === '') {
      return
    }

    try {
      setRecipientSearch(
        await getJson(
          `/mail/${characterId}/search-recipient?search_term=${encodeURIComponent(recipientSearchTerm.trim())}`,
          lotdgMailRecipientSearchSchema,
        ),
      )
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const send = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    try {
      const result = await postForm(`/mail/${characterId}/send`, lotdgMailMutationSchema, {
        recipient_login_name: recipientLoginName,
        subject,
        body,
      })

      if (result.sent !== true) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))

        return
      }

      setRecipientLoginName('')
      setSubject('')
      setBody('')
      setMessage(label('mail.sent'))
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('mail.title')}</h2>

      {inbox !== null && (
        <p>
          {label('mail.summary', {
            unseen: inbox.unseen_count,
            seen: inbox.seen_count,
          })}
        </p>
      )}

      <table className="lotdg-stat">
        <tbody>
          <tr>
            <th className="lotdg-stat__head">{label('mail.column.select')}</th>
            <th className="lotdg-stat__head">{label('mail.column.sender')}</th>
            <th className="lotdg-stat__head">{label('mail.column.subject')}</th>
            <th className="lotdg-stat__head">{label('mail.column.action')}</th>
          </tr>
          {inbox?.message_list.map((item) => (
            <tr key={item.mail_message_id}>
              <td className="lotdg-stat__value">
                <input
                  type="checkbox"
                  checked={checkedIdList.includes(item.mail_message_id)}
                  onChange={(event) =>
                    setCheckedIdList((previous) =>
                      event.target.checked
                        ? [...previous, item.mail_message_id]
                        : previous.filter((identifier) => identifier !== item.mail_message_id),
                    )
                  }
                />
              </td>
              <td className="lotdg-stat__value">{item.sender_display_name}</td>
              <td className="lotdg-stat__value">
                {item.is_seen ? item.subject : <b>{item.subject}</b>}
              </td>
              <td className="lotdg-stat__value">
                <button
                  type="button"
                  className="lotdg-button"
                  onClick={() => void open(item.mail_message_id)}
                >
                  {label('mail.action.read')}
                </button>{' '}
                <button
                  type="button"
                  className="lotdg-button"
                  onClick={() => void remove(item.mail_message_id)}
                >
                  {label('mail.action.delete')}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      <p>
        <button
          type="button"
          className="lotdg-button"
          onClick={() =>
            setCheckedIdList(
              (inbox?.message_list ?? []).map((item) => item.mail_message_id),
            )
          }
        >
          {label('mail.action.check-all')}
        </button>{' '}
        <button
          type="button"
          className="lotdg-button"
          disabled={checkedIdList.length === 0}
          onClick={() => void removeChecked()}
        >
          {label('mail.action.delete-checked')}
        </button>
      </p>

      {opened?.found === true && (
        <div>
          <h3>{opened.subject}</h3>
          <p>{parseLegacyMarkup(opened.body ?? '')}</p>
          <p>
            <button type="button" className="lotdg-button" onClick={() => void prepareReply()}>
              {label('mail.action.reply')}
            </button>
          </p>
        </div>
      )}

      <form onSubmit={(event) => void searchRecipient(event)}>
        <p>
          <label htmlFor="lotdg-mail-recipient-search">{label('mail.search-recipient')}</label>{' '}
          <input
            id="lotdg-mail-recipient-search"
            className="lotdg-input"
            value={recipientSearchTerm}
            onChange={(event) => setRecipientSearchTerm(event.target.value)}
          />{' '}
          <button type="submit" className="lotdg-button">
            {label('mail.action.search')}
          </button>
        </p>
      </form>

      {recipientSearch !== null && (
        <p>
          {recipientSearch.candidate_list.length === 0 ? (
            <span className="colDkWhite">{label('mail.search-empty')}</span>
          ) : (
            recipientSearch.candidate_list.map((candidate) => (
              <button
                key={candidate.login_name}
                type="button"
                className="lotdg-button"
                onClick={() => setRecipientLoginName(candidate.login_name)}
              >
                {candidate.display_name}
              </button>
            ))
          )}
        </p>
      )}

      <form onSubmit={(event) => void send(event)}>
        <h3>{label('mail.compose')}</h3>
        <p>
          <input
            className="lotdg-input"
            placeholder={label('mail.column.recipient')}
            value={recipientLoginName}
            onChange={(event) => setRecipientLoginName(event.target.value)}
          />
        </p>
        <p>
          <input
            className="lotdg-input"
            placeholder={label('mail.column.subject')}
            value={subject}
            onChange={(event) => setSubject(event.target.value)}
          />
        </p>
        <p>
          <textarea
            className="lotdg-input"
            rows={4}
            value={body}
            onChange={(event) => setBody(event.target.value)}
          />
        </p>
        <button type="submit" className="lotdg-button">
          {label('mail.action.send')}
        </button>
      </form>

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
