import { useState, type FormEvent } from 'react'
import { postForm } from '../../shared/lib/lotdg-api-client'
import { lotdgRegisterResponseSchema } from '../../shared/schema/system/lotdg-api-response-schema'
import { useLotdgSession } from '../../app/provider/useLotdgSession'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'

export function LotdgRegisterScreen({ onLoginClick }: { readonly onLoginClick: () => void }) {
  const { signIn } = useLotdgSession()
  const { translate, localeCode } = useLotdgLocale()

  const [loginName, setLoginName] = useState('')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [emailAddress, setEmailAddress] = useState('')
  const [sexCode, setSexCode] = useState(0)
  const [errorKeyList, setErrorKeyList] = useState<string[]>([])
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
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

  return (
    <section>
      <h2>{translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'register.title')}</h2>

      <form onSubmit={handleSubmit}>
        <p>
          <label htmlFor="register-login-name">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.login-name')}
          </label>
          <br />
          <input
            id="register-login-name"
            className="lotdg-input"
            value={loginName}
            onChange={(event) => setLoginName(event.target.value)}
          />
        </p>

        <p>
          <label htmlFor="register-password">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.password')}
          </label>
          <br />
          <input
            id="register-password"
            className="lotdg-input"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />
        </p>

        <p>
          <label htmlFor="register-password-confirmation">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.password-confirmation')}
          </label>
          <br />
          <input
            id="register-password-confirmation"
            className="lotdg-input"
            type="password"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
          />
        </p>

        <p>
          <label htmlFor="register-email">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.email-address')}
          </label>
          <br />
          <input
            id="register-email"
            className="lotdg-input"
            value={emailAddress}
            onChange={(event) => setEmailAddress(event.target.value)}
          />
        </p>

        <p>
          <label htmlFor="register-sex">
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.sex')}
          </label>
          <br />
          <select
            id="register-sex"
            className="lotdg-select"
            value={sexCode}
            onChange={(event) => setSexCode(Number(event.target.value))}
          >
            <option value={0}>
              {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.sex-male')}
            </option>
            <option value={1}>
              {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'field.sex-female')}
            </option>
          </select>
        </p>

        {errorKeyList.length > 0 && (
          <ul className="colLtRed">
            {errorKeyList.map((errorKey) => (
              <li key={errorKey}>
                {translate(
                  LOTDG_LOCALE_NAMESPACE.AUTHENTICATION,
                  errorKey.replace(/^authentication\./, ''),
                )}
              </li>
            ))}
          </ul>
        )}

        <p>
          <button type="submit" className="lotdg-button" disabled={isSubmitting}>
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'action.create-character')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={onLoginClick}>
            {translate(LOTDG_LOCALE_NAMESPACE.AUTHENTICATION, 'action.back-to-login')}
          </button>
        </p>
      </form>
    </section>
  )
}
