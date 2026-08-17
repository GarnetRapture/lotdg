import { useCallback, useEffect, useState } from 'react'
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
import {
  LOTDG_BIOGRAPHY_MAXIMUM_LENGTH,
  LOTDG_NOTIFICATION_PREFERENCE_KEY_LIST,
} from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_BOOLEAN_FIELD_VALUE } from '../../shared/constant/lotdg-form-token'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgCheckboxField,
  LotdgFieldRow,
  LotdgForm,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgSelectField,
  LotdgSubmitButton,
  LotdgTextField,
} from '../../shared/ui'

const NOTIFICATION_KEY_LIST = LOTDG_NOTIFICATION_PREFERENCE_KEY_LIST

const LOTDG_ENABLED_NOTIFICATION_VALUE = 1

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
            NOTIFICATION_KEY_LIST.map((key) => [
              key,
              Number(result.notification[key] ?? 0) === LOTDG_ENABLED_NOTIFICATION_VALUE,
            ]),
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

  const save = async () => {
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
            NOTIFICATION_KEY_LIST.map((key) => [
              key,
              notificationMap[key] === true
                ? LOTDG_BOOLEAN_FIELD_VALUE.TRUE
                : LOTDG_BOOLEAN_FIELD_VALUE.FALSE,
            ]),
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

  const changePassword = async () => {
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
    <LotdgScreen titleText={label('preference.title')}>
      {preference !== null && (
        <>
          <LotdgForm onSubmit={() => void save()}>
            <LotdgFieldRow isStacked>
              <LotdgSelectField
                labelText={label('preference.field.locale')}
                value={selectedLocaleCode}
                onValueChange={setSelectedLocaleCode}
                optionList={LOTDG_SUPPORTED_LOCALE_CODE_LIST.map((code) => ({
                  optionValue: code,
                  labelText: LOTDG_SUPPORTED_LOCALE_ENDONYM[code],
                }))}
              />
            </LotdgFieldRow>

            <LotdgFieldRow isStacked>
              <LotdgTextField
                labelText={label('preference.field.email')}
                value={emailAddress}
                onValueChange={setEmailAddress}
                isDisabled={!preference.email_change_allowed}
              />
            </LotdgFieldRow>

            <LotdgFieldRow isStacked>
              <LotdgTextField
                labelText={label('preference.field.biography')}
                value={biography}
                onValueChange={setBiography}
                maximumLength={LOTDG_BIOGRAPHY_MAXIMUM_LENGTH}
                isDisabled={!preference.biography_editable}
              />
            </LotdgFieldRow>

            {NOTIFICATION_KEY_LIST.map((key) => (
              <LotdgFieldRow key={key}>
                <LotdgCheckboxField
                  labelText={label(`preference.field.${key}`)}
                  isChecked={notificationMap[key] === true}
                  onCheckedChange={(nextChecked) =>
                    setNotificationMap((previous) => ({ ...previous, [key]: nextChecked }))
                  }
                />
              </LotdgFieldRow>
            ))}

            <LotdgActionRow>
              <LotdgSubmitButton labelSlot={label('preference.action.save')} />
            </LotdgActionRow>
          </LotdgForm>

          <LotdgSection titleSlot={label('preference.password-title')}>
            <LotdgForm onSubmit={() => void changePassword()}>
              <LotdgFieldRow isStacked>
                <LotdgTextField
                  labelText={label('preference.field.password')}
                  value={password}
                  onValueChange={setPassword}
                  isSecret
                />
              </LotdgFieldRow>

              <LotdgFieldRow isStacked>
                <LotdgTextField
                  labelText={label('preference.field.password-confirmation')}
                  value={passwordConfirmation}
                  onValueChange={setPasswordConfirmation}
                  isSecret
                />
              </LotdgFieldRow>

              <LotdgActionRow>
                <LotdgSubmitButton labelSlot={label('preference.action.change-password')} />
              </LotdgActionRow>
            </LotdgForm>
          </LotdgSection>
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
