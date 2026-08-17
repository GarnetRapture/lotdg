import { useState } from 'react'
import { postForm } from '../../shared/lib/lotdg-api-client'
import { lotdgRegisterResponseSchema } from '../../shared/schema/system/lotdg-api-response-schema'
import { useLotdgSession } from '../../app/provider/useLotdgSession'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_SEX_CODE } from '../../shared/constant/lotdg-legacy-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_AUTOCOMPLETE_TOKEN } from '../../shared/constant/lotdg-form-token'
import type { LotdgRegisterScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgFieldRow,
  LotdgForm,
  LotdgMessageList,
  LotdgScreen,
  LotdgSelectField,
  LotdgSubmitButton,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_AUTHENTICATION_LABEL_PREFIX = /^authentication\./

export function LotdgRegisterScreen({ onLoginClick }: LotdgRegisterScreenProps) {
  const { signIn } = useLotdgSession()
  const { translate, localeCode } = useLotdgLocale()

  const [loginName, setLoginName] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [emailAddress, setEmailAddress] = useState('')
  const [sexCode, setSexCode] = useState<number>(LOTDG_SEX_CODE.MALE)
  const [errorKeyList, setErrorKeyList] = useState<string[]>([])
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async () => {
    setErrorKeyList([])
    setIsSubmitting(true)

    try {
      const result = await postForm('/authentication/register', lotdgRegisterResponseSchema, {
        login_name: loginName,
        password,
        password_confirmation: passwordConfirmation,
        email_address: emailAddress,
        sex_code: sexCode,
        locale_code: localeCode,
      })

      if (
        !result.registered ||
        result.account_id === undefined ||
        result.character_id === undefined
      ) {
        setErrorKeyList(result.message_key_list ?? ['authentication.error.unknown'])

        return
      }

      signIn({
        accountId: result.account_id,
        characterId: result.character_id,
        loginName: result.login_name ?? loginName,
        superuserLevel: 0,
        storedLocaleCode: localeCode,
      })
    } catch (error) {
      setErrorKeyList([resolveErrorLabel(error, translate)])
    } finally {
      setIsSubmitting(false)
    }
  }

  const label = (path: string) => translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, path)

  return (
    <LotdgScreen titleText={label('register.title')}>
      <LotdgForm onSubmit={() => void handleSubmit()}>
        <LotdgFieldRow isStacked>
          <LotdgTextField
            labelText={label('field.login-name')}
            value={loginName}
            onValueChange={setLoginName}
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.USERNAME}
          />
        </LotdgFieldRow>

        <LotdgFieldRow isStacked>
          <LotdgTextField
            labelText={label('field.password')}
            value={password}
            onValueChange={setPassword}
            isSecret
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.NEW_PASSWORD}
          />
        </LotdgFieldRow>

        <LotdgFieldRow isStacked>
          <LotdgTextField
            labelText={label('field.password-confirmation')}
            value={passwordConfirmation}
            onValueChange={setPasswordConfirmation}
            isSecret
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.NEW_PASSWORD}
          />
        </LotdgFieldRow>

        <LotdgFieldRow isStacked>
          <LotdgTextField
            labelText={label('field.email-address')}
            value={emailAddress}
            onValueChange={setEmailAddress}
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.EMAIL_ADDRESS}
          />
        </LotdgFieldRow>

        <LotdgFieldRow isStacked>
          <LotdgSelectField
            labelText={label('field.sex')}
            value={String(sexCode)}
            onValueChange={(nextValue) => setSexCode(Number(nextValue))}
            optionList={[
              { optionValue: String(LOTDG_SEX_CODE.MALE), labelText: label('field.sex-male') },
              {
                optionValue: String(LOTDG_SEX_CODE.FEMALE),
                labelText: label('field.sex-female'),
              },
            ]}
          />
        </LotdgFieldRow>

        <LotdgMessageList
          messageTextList={errorKeyList.map((errorKey) =>
            label(errorKey.replace(LOTDG_AUTHENTICATION_LABEL_PREFIX, '')),
          )}
          colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}
        />

        <LotdgActionRow>
          <LotdgSubmitButton
            labelSlot={label('action.create-character')}
            isDisabled={isSubmitting}
          />
          <LotdgButton labelSlot={label('action.back-to-login')} onSelect={onLoginClick} />
        </LotdgActionRow>
      </LotdgForm>
    </LotdgScreen>
  )
}
