import { useEffect, useState } from 'react'
import { getJson } from '../../shared/lib/lotdg-api-client'
import {
  lotdgBiographySchema,
  type LotdgBiography,
} from '../../shared/schema/social/lotdg-social-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveNewsText } from '../../shared/lib/lotdg-news-label-resolver'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_RACE_LABEL_PATH,
  LOTDG_SEX_CODE,
  LOTDG_SPECIALTY_LABEL_PATH,
} from '../../shared/constant/lotdg-legacy-code'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgBiographyScreen({ characterId }: LotdgCharacterScreenProps) {
  const { translate } = useLotdgLocale()
  const [biography, setBiography] = useState<LotdgBiography | null>(null)
  const [errorMessage, setErrorMessage] = useState('')

  useEffect(() => {
    let isMounted = true

    getJson(`/biography/${characterId}`, lotdgBiographySchema)
      .then((result) => {
        if (isMounted) {
          setBiography(result)
        }
      })
      .catch((error: unknown) => {
        if (isMounted) {
          setErrorMessage(resolveErrorLabel(error, translate))
        }
      })

    return () => {
      isMounted = false
    }
  }, [characterId, translate])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const commonLabel = (path: string) => translate(LOTDG_LOCALE_NAMESPACE.COMMON, path)

  return (
    <section>
      <h2>{label('biography.title')}</h2>

      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />

      {biography !== null && (
        <>
          <p className="colLtWhite">
            {biography.rank_title} {biography.display_name}
          </p>

          <table className="lotdg-stat">
            <tbody>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.login-name')}</td>
                <td className="lotdg-stat__value">{biography.login_name}</td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.level')}</td>
                <td className="lotdg-stat__value">{biography.level}</td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.sex')}</td>
                <td className="lotdg-stat__value">
                  {commonLabel(
                    biography.sex_code === LOTDG_SEX_CODE.FEMALE ? 'sex.female' : 'sex.male',
                  )}
                </td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.race')}</td>
                <td className="lotdg-stat__value">
                  {commonLabel(LOTDG_RACE_LABEL_PATH[biography.race_code] ?? 'race.unknown')}
                </td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.specialty')}</td>
                <td className="lotdg-stat__value">
                  {commonLabel(
                    LOTDG_SPECIALTY_LABEL_PATH[biography.specialty_code] ?? 'specialty.none',
                  )}
                </td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.dragon-kill')}</td>
                <td className="lotdg-stat__value">{biography.dragon_kill_count}</td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.resurrection')}</td>
                <td className="lotdg-stat__value">{biography.resurrection_count}</td>
              </tr>
              <tr>
                <td className="lotdg-stat__label">{label('biography.field.mount')}</td>
                <td className="lotdg-stat__value">
                  {biography.mount_name ?? label('biography.no-mount')}
                </td>
              </tr>
            </tbody>
          </table>

          <h3>{label('biography.body-title')}</h3>
          <p>
            {biography.biography === ''
              ? label('biography.body-empty')
              : parseLegacyMarkup(biography.biography)}
          </p>

          <h3>{label('biography.news-title')}</h3>
          {biography.news_history.length === 0 ? (
            <p className="colDkWhite">{label('biography.news-empty')}</p>
          ) : (
            biography.news_history.map((news) => (
              <p key={news.news_id}>
                <span className="colDkWhite">{news.news_date}</span>{' '}
                {parseLegacyMarkup(resolveNewsText(news.news_text, translate))}
              </p>
            ))
          )}
        </>
      )}
    </section>
  )
}
