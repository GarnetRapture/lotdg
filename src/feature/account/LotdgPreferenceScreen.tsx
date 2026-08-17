import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgPreferenceInspectSchema,
  lotdgPreferenceMutationSchema,
  type LotdgPreferenceInspect,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import {
  LOTDG_LOCALE_NAMESPACE,
  LOTDG_SUPPORTED_LOCALE_CODE_LIST,
  LOTDG_SUPPORTED_LOCALE_ENDONYM,
} from '../../shared/constant/lotdg-supported-locale'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

const NOTIFICATION_KEY_LIST = ['emailonmail', 'systemmail', 'dirtyemail'] as const

export function LotdgPreferenceScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate, setLocaleCode } = useLotdgLocale()
  const [preference, setPreference] = useState<LotdgPreferenceInspect | null>(null)
  const [emailAddress, setEmailAddress] = useState('')
  const [biography, setBiography] = useState('')
  const [selectedLocaleCode, setSelectedLocaleCode] = useState('')
  const [notificationMap, setNotificationMap] = useState<Record<string, boolean>>({})
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/preference/${characterId}/inspect`, lotdgPreferenceInspectSchema)
      .then((result) => {
        setPreference(result)
        setEmailAddress(result.email_address)
        setBiography(result.biography)
        setSelectedLocaleCode(result.locale_code)
        setNotificationMap(
          Object.fromEntries(
            NOTIFICATION_KEY_LIST.map((key) => [key, Number(result.notification[key] ?? 0) === 1]),
          ),
        )
      })
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const save = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    if (preference === null) {
      return
    }

    try {
      const result = await postForm(
        `/preference/${characterId}/save`,
        lotdgPreferenceMutationSchema,
        {
          locale_code: selectedLocaleCode,
          template_name: preference.template_name,
          email_address: emailAddress,
          biography,
          ...Object.fromEntries(
            NOTIFICATION_KEY_LIST.map((key) => [key, notificationMap[key] === true ? '1' : '0']),
          ),
        },
      )

      const noticeKeyList = result.notice_key_list ?? []

      setMessage(
        noticeKeyList.length === 0
          ? label('preference.saved')
          : noticeKeyList
              .map((noticeKey) => resolveMessageKeyLabel(noticeKey, translate))
              .join(' '),
      )

      if (LOTDG_SUPPORTED_LOCALE_CODE_LIST.some((code) => code === selectedLocaleCode)) {
        setLocaleCode(selectedLocaleCode as (typeof LOTDG_SUPPORTED_LOCALE_CODE_LIST)[number])
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const changePassword = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()

    try {
      const result = await postForm(
        `/preference/${characterId}/password`,
        lotdgPreferenceMutationSchema,
        { password, password_confirmation: passwordConfirmation },
      )

      setMessage(
        result.changed === true
          ? label('preference.password-changed')
          : resolveMessageKeyLabel(result.message_key, translate),
      )

      setPassword('')
      setPasswordConfirmation('')
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('preference.title')}</h2>

      {preference !== null && (
        <>
          <form onSubmit={(event) => void save(event)}>
            <p>
              <label htmlFor="lotdg-preference-locale">{label('preference.field.locale')}</label>
              <br />
              <select
                id="lotdg-preference-locale"
                className="lotdg-select"
                value={selectedLocaleCode}
                onChange={(event) => setSelectedLocaleCode(event.target.value)}
              >
                {LOTDG_SUPPORTED_LOCALE_CODE_LIST.map((code) => (
                  <option key={code} value={code}>
                    {LOTDG_SUPPORTED_LOCALE_ENDONYM[code]}
                  </option>
                ))}
              </select>
            </p>

            <p>
              <label htmlFor="lotdg-preference-email">{label('preference.field.email')}</label>
              <br />
              <input
                id="lotdg-preference-email"
                className="lotdg-input"
                value={emailAddress}
                disabled={!preference.email_change_allowed}
                onChange={(event) => setEmailAddress(event.target.value)}
              />
            </p>

            <p>
              <label htmlFor="lotdg-preference-biography">
                {label('preference.field.biography')}
              </label>
              <br />
              <input
                id="lotdg-preference-biography"
                className="lotdg-input"
                value={biography}
                maxLength={255}
                disabled={!preference.biography_editable}
                onChange={(event) => setBiography(event.target.value)}
              />
            </p>

            {NOTIFICATION_KEY_LIST.map((key) => (
              <p key={key}>
                <label htmlFor={`lotdg-preference-${key}`}>
                  <input
                    id={`lotdg-preference-${key}`}
                    type="checkbox"
                    checked={notificationMap[key] === true}
                    onChange={(event) =>
                      setNotificationMap((previous) => ({
                        ...previous,
                        [key]: event.target.checked,
                      }))
                    }
                  />{' '}
                  {label(`preference.field.${key}`)}
                </label>
              </p>
            ))}

            <p>
              <button type="submit" className="lotdg-button">
                {label('preference.action.save')}
              </button>
            </p>
          </form>

          <h3>{label('preference.password-title')}</h3>

          <form onSubmit={(event) => void changePassword(event)}>
            <p>
              <label htmlFor="lotdg-preference-password">
                {label('preference.field.password')}
              </label>
              <br />
              <input
                id="lotdg-preference-password"
                className="lotdg-input"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
              />
            </p>

            <p>
              <label htmlFor="lotdg-preference-password-confirmation">
                {label('preference.field.password-confirmation')}
              </label>
              <br />
              <input
                id="lotdg-preference-password-confirmation"
                className="lotdg-input"
                type="password"
                value={passwordConfirmation}
                onChange={(event) => setPasswordConfirmation(event.target.value)}
              />
            </p>

            <p>
              <button type="submit" className="lotdg-button">
                {label('preference.action.change-password')}
              </button>
            </p>
          </form>
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
