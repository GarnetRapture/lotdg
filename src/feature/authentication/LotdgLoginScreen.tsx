import { useState, type FormEvent } from 'react'
import { postForm } from '../../shared/lib/lotdg-api-client'
import { lotdgLoginResponseSchema } from '../../shared/schema/system/lotdg-api-response-schema'
import { useLotdgSession } from '../../app/provider/useLotdgSession'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgLoginScreen({ onRegisterClick }: { readonly onRegisterClick: () => void }) {
  const { signIn } = useLotdgSession()
  const { translate } = useLotdgLocale()

  const [loginName, setLoginName] = useState('')
  const [password, setPassword] = useState('')
  const [errorMessage, setErrorMessage] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

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
      })
    } catch (error) {
      setErrorMessage(resolveErrorLabel(error, translate))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section>
      <h2>{translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'login.title')}</h2>

      <form onSubmit={handleSubmit}>
        <p>
          <label htmlFor="login-name">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.login-name')}
          </label>
          <br />
          <input
            id="login-name"
            className="lotdg-input"
            value={loginName}
            onChange={(event) => setLoginName(event.target.value)}
            autoComplete="username"
          />
        </p>

        <p>
          <label htmlFor="password">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.password')}
          </label>
          <br />
          <input
            id="password"
            className="lotdg-input"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            autoComplete="current-password"
          />
        </p>

        {errorMessage !== '' && <p className="colLtRed">{errorMessage}</p>}

        <p>
          <button type="submit" className="lotdg-button" disabled={isSubmitting}>
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'action.login')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={onRegisterClick}>
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'action.create-character')}
          </button>
        </p>
      </form>
    </section>
  )
}
