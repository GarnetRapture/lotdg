import { useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import { lotdgLoginResponseSchema } from '../../shared/schema/system/lotdg-api-response-schema'
import {
  lotdgGameInformationSchema,
  type LotdgGameInformation,
} from '../../shared/schema/catalog/lotdg-editor-schema'
import { useLotdgSession } from '../../app/provider/useLotdgSession'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { splitDuration } from '../../shared/lib/lotdg-duration-formatter'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_CONTROL_WIDTH_CODE } from '../../shared/constant/lotdg-ui-class-name'
import {
  LOTDG_ACCESS_KEY,
  LOTDG_AUTOCOMPLETE_TOKEN,
} from '../../shared/constant/lotdg-form-token'
import type { LotdgLoginScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgForm,
  LotdgLoginPanel,
  LotdgMarkupText,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

export function LotdgLoginScreen({ onRegisterClick }: LotdgLoginScreenProps) {
  const { signIn } = useLotdgSession()
  const { translate } = useLotdgLocale()

  const [loginName, setLoginName] = useState('')
  const [password, setPassword] = useState('')
  const [errorMessage, setErrorMessage] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [information, setInformation] = useState<LotdgGameInformation | null>(null)

  useEffect(() => {
    getJson('/game-information', lotdgGameInformationSchema)
      .then(setInformation)
      .catch(() => {
        setInformation(null)
      })
  }, [])

  const handleSubmit = async () => {
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      const result = await postForm('/authentication/login', lotdgLoginResponseSchema, {
        login_name: loginName,
        password,
      })

      if (
        !result.authenticated ||
        result.account_id === undefined ||
        result.character_id === undefined ||
        result.character_id === null
      ) {
        setErrorMessage(
          translate(
            LOTDG_LOCALE_NAMESPACE.AUTHENTICATION,
            result.message_key?.split('.').slice(1).join('.') ?? 'error.credential-mismatch',
          ),
        )

        return
      }

      signIn({
        accountId: result.account_id,
        characterId: result.character_id,
        loginName,
        superuserLevel: result.privilege?.superuser_level ?? 0,
        storedLocaleCode: result.preference?.locale_code,
      })
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    } finally {
      setIsSubmitting(false)
    }
  }

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, path, valueMap)

  return (
    <>
      <LotdgText isCentered>{label('login.welcome')}</LotdgText>

      {information !== null && (
        <>
          <LotdgMarkupText
            sourceText={label('login.game-time', {
              gameTime: information.game_time,
              gameDate: information.game_date,
            })}
          />
          <LotdgMarkupText
            sourceText={label('login.new-day-clock', {
              minute: information.day_duration_real_minute,
              turn: information.turns_per_day,
            })}
          />
          <LotdgMarkupText
            sourceText={label(
              'login.next-day-remaining',
              splitDuration(information.real_seconds_until_next_game_day),
            )}
          />
        </>
      )}

      <LotdgText>{label('login.prompt')}</LotdgText>

      {errorMessage !== '' && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_RED}>
          {errorMessage}
        </LotdgText>
      )}

      <LotdgForm onSubmit={() => void handleSubmit()}>
        <LotdgLoginPanel>
          <LotdgTextField
            labelText={label('field.login-name')}
            value={loginName}
            onValueChange={setLoginName}
            widthCode={LOTDG_CONTROL_WIDTH_CODE.LOGIN_PANEL}
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.USERNAME}
            accessKey={LOTDG_ACCESS_KEY.LOGIN_NAME}
          />

          <LotdgTextField
            labelText={label('field.password')}
            value={password}
            onValueChange={setPassword}
            isSecret
            widthCode={LOTDG_CONTROL_WIDTH_CODE.LOGIN_PANEL}
            autocompleteToken={LOTDG_AUTOCOMPLETE_TOKEN.CURRENT_PASSWORD}
            accessKey={LOTDG_ACCESS_KEY.PASSWORD}
          />

          <LotdgSubmitButton labelSlot={label('action.login')} isDisabled={isSubmitting} />
        </LotdgLoginPanel>
      </LotdgForm>

      <LotdgActionRow>
        <LotdgButton labelSlot={label('action.create-character')} onSelect={onRegisterClick} />
      </LotdgActionRow>

      <LotdgMarkupText sourceText={label('login.introduction')} />

      <LotdgMarkupText sourceText={label('login.banner')} />

      <LotdgMarkupText
        sourceText={label('login.version', { version: information?.legacy_version ?? '' })}
        isCentered
      />
    </>
  )
}
