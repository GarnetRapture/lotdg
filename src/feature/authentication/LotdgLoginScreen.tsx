import { useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import { lotdgLoginResponseSchema } from '../../shared/schema/system/lotdg-api-response-schema'
import {
  lotdgGameInformationSchema,
  type LotdgGameInformation,
} from '../../shared/schema/catalog/lotdg-editor-schema'
import { useLotdgSession } from '../../app/provider/useLotdgSession'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { splitDuration } from '../../shared/lib/lotdg-duration-formatter'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgLoginScreen({ onRegisterClick }: { readonly onRegisterClick: () => void }) {
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

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
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
    <section>
      <p className="lotdg-align-center">{label('login.welcome')}</p>

      {information !== null && (
        <>
          <p>
            {parseLegacyMarkup(
              label('login.game-time', {
                gameTime: information.game_time,
                gameDate: information.game_date,
              }),
            )}
          </p>
          <p>
            {parseLegacyMarkup(
              label('login.new-day-clock', {
                minute: information.day_duration_real_minute,
                turn: information.turns_per_day,
              }),
            )}
          </p>
          <p>
            {parseLegacyMarkup(
              label(
                'login.next-day-remaining',
                splitDuration(information.real_seconds_until_next_game_day),
              ),
            )}
          </p>
        </>
      )}

      <p>{label('login.prompt')}</p>

      {errorMessage !== '' && <p className="colLtRed">{errorMessage}</p>}

      <form onSubmit={handleSubmit}>
        <div className="lotdg-align-center">
          <div className="lotdg-login-panel">
            <label htmlFor="login-name">{label('field.login-name')}</label>
            <input
              id="login-name"
              className="lotdg-input lotdg-login-panel__field"
              value={loginName}
              onChange={(event) => setLoginName(event.target.value)}
              autoComplete="username"
              accessKey="u"
            />

            <label htmlFor="password">{label('field.password')}</label>
            <input
              id="password"
              className="lotdg-input lotdg-login-panel__field"
              type="password"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              autoComplete="current-password"
              accessKey="p"
            />

            <button type="submit" className="lotdg-button" disabled={isSubmitting}>
              {label('action.login')}
            </button>
          </div>
        </div>
      </form>

      <p>
        <button type="button" className="lotdg-button" onClick={onRegisterClick}>
          {label('action.create-character')}
        </button>
      </p>

      <p>{parseLegacyMarkup(label('login.introduction'))}</p>

      <p>{parseLegacyMarkup(label('login.banner'))}</p>

      <p className="lotdg-align-center">
        {parseLegacyMarkup(label('login.version', { version: information?.legacy_version ?? '' }))}
      </p>
    </section>
  )
}
