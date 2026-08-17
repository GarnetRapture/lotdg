import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgMailInboxSchema,
  lotdgMailMutationSchema,
  lotdgMailReadSchema,
  lotdgMailRecipientSearchSchema,
  lotdgMailReplySchema,
  type LotdgMailInbox,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgCheckboxField,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgForm,
  LotdgMarkupText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextAreaField,
  LotdgTextField,
} from '../../shared/ui'
import type { z } from 'zod'

type MailRead = z.infer<typeof lotdgMailReadSchema>
type MailRecipientSearch = z.infer<typeof lotdgMailRecipientSearchSchema>

const LOTDG_MAIL_NO_OPENED_ID = 0

export function LotdgMailScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [inbox, setInbox] = useState<LotdgMailInbox | null>(null)
  const [opened, setOpened] = useState<MailRead | null>(null)
  const [openedId, setOpenedId] = useState(LOTDG_MAIL_NO_OPENED_ID)
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
      setOpenedId(LOTDG_MAIL_NO_OPENED_ID)
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
          checkedIdList.map((identifier, index) => [`mail_message_id_list[${index}]`, identifier]),
        ),
      )

      setMessage(
        result.message_key === undefined
          ? label('mail.deleted-many', { count: result.deleted_count ?? 0 })
          : resolveMessageKeyLabel(result.message_key, translate),
      )
      setCheckedIdList([])
      setOpened(null)
      setOpenedId(LOTDG_MAIL_NO_OPENED_ID)
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const prepareReply = async () => {
    if (openedId <= LOTDG_MAIL_NO_OPENED_ID) {
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

  const searchRecipient = async () => {
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

  const send = async () => {
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
    <LotdgScreen titleText={label('mail.title')}>
      {inbox !== null && (
        <LotdgText>
          {label('mail.summary', {
            unseen: inbox.unseen_count,
            seen: inbox.seen_count,
          })}
        </LotdgText>
      )}

      <LotdgDataTable
        rowList={inbox?.message_list ?? []}
        rowKey={(item) => item.mail_message_id}
        columnList={[
          {
            columnKey: 'select',
            headText: label('mail.column.select'),
            render: (item) => (
              <LotdgCheckboxField
                labelText={label('mail.column.select')}
                isLabelHidden
                isChecked={checkedIdList.includes(item.mail_message_id)}
                onCheckedChange={(nextChecked) =>
                  setCheckedIdList((previous) =>
                    nextChecked
                      ? [...previous, item.mail_message_id]
                      : previous.filter((identifier) => identifier !== item.mail_message_id),
                  )
                }
              />
            ),
          },
          {
            columnKey: 'sender',
            headText: label('mail.column.sender'),
            render: (item) => item.sender_display_name,
          },
          {
            columnKey: 'subject',
            headText: label('mail.column.subject'),
            render: (item) => (item.is_seen ? item.subject : <b>{item.subject}</b>),
          },
          {
            columnKey: 'action',
            headText: label('mail.column.action'),
            render: (item) => (
              <LotdgActionRow>
                <LotdgButton
                  labelSlot={label('mail.action.read')}
                  onSelect={() => void open(item.mail_message_id)}
                />
                <LotdgButton
                  labelSlot={label('mail.action.delete')}
                  onSelect={() => void remove(item.mail_message_id)}
                />
              </LotdgActionRow>
            ),
          },
        ]}
      />

      <LotdgActionRow>
        <LotdgButton
          labelSlot={label('mail.action.check-all')}
          onSelect={() =>
            setCheckedIdList((inbox?.message_list ?? []).map((item) => item.mail_message_id))
          }
        />
        <LotdgButton
          labelSlot={label('mail.action.delete-checked')}
          isDisabled={checkedIdList.length === 0}
          onSelect={() => void removeChecked()}
        />
      </LotdgActionRow>

      {opened?.found === true && (
        <LotdgSection titleSlot={opened.subject}>
          <LotdgMarkupText sourceText={opened.body ?? ''} />
          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('mail.action.reply')}
              onSelect={() => void prepareReply()}
            />
          </LotdgActionRow>
        </LotdgSection>
      )}

      <LotdgForm onSubmit={() => void searchRecipient()}>
        <LotdgFieldRow>
          <LotdgTextField
            labelText={label('mail.search-recipient')}
            value={recipientSearchTerm}
            onValueChange={setRecipientSearchTerm}
          />
          <LotdgSubmitButton labelSlot={label('mail.action.search')} />
        </LotdgFieldRow>
      </LotdgForm>

      {recipientSearch !== null && (
        <LotdgActionRow>
          {recipientSearch.candidate_list.length === 0 ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {label('mail.search-empty')}
            </LotdgText>
          ) : (
            recipientSearch.candidate_list.map((candidate) => (
              <LotdgButton
                key={candidate.login_name}
                labelSlot={candidate.display_name}
                onSelect={() => setRecipientLoginName(candidate.login_name)}
              />
            ))
          )}
        </LotdgActionRow>
      )}

      <LotdgSection titleSlot={label('mail.compose')}>
        <LotdgForm onSubmit={() => void send()}>
          <LotdgFieldRow isStacked>
            <LotdgTextField
              value={recipientLoginName}
              onValueChange={setRecipientLoginName}
              placeholderText={label('mail.column.recipient')}
            />
          </LotdgFieldRow>
          <LotdgFieldRow isStacked>
            <LotdgTextField
              value={subject}
              onValueChange={setSubject}
              placeholderText={label('mail.column.subject')}
            />
          </LotdgFieldRow>
          <LotdgFieldRow isStacked>
            <LotdgTextAreaField value={body} onValueChange={setBody} />
          </LotdgFieldRow>
          <LotdgActionRow>
            <LotdgSubmitButton labelSlot={label('mail.action.send')} />
          </LotdgActionRow>
        </LotdgForm>
      </LotdgSection>

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
