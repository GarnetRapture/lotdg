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
import { LOTDG_NOTICE_TONE } from '../../shared/constant/lotdg-notice-tone'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'
import type { LotdgStatSection } from '../../shared/type/lotdg-ui-component-contract'
import {
  LotdgInlineText,
  LotdgMarkupText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgStatTable,
  LotdgText,
} from '../../shared/ui'

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

  const profileSectionList: ReadonlyArray<LotdgStatSection> =
    biography === null
      ? []
      : [
          {
            sectionKey: 'profile',
            entryList: [
              {
                entryKey: 'login-name',
                labelText: label('biography.field.login-name'),
                valueSlot: biography.login_name,
              },
              {
                entryKey: 'level',
                labelText: label('biography.field.level'),
                valueSlot: biography.level,
              },
              {
                entryKey: 'sex',
                labelText: label('biography.field.sex'),
                valueSlot: commonLabel(
                  biography.sex_code === LOTDG_SEX_CODE.FEMALE ? 'sex.female' : 'sex.male',
                ),
              },
              {
                entryKey: 'race',
                labelText: label('biography.field.race'),
                valueSlot: commonLabel(
                  LOTDG_RACE_LABEL_PATH[biography.race_code] ?? 'race.unknown',
                ),
              },
              {
                entryKey: 'specialty',
                labelText: label('biography.field.specialty'),
                valueSlot: commonLabel(
                  LOTDG_SPECIALTY_LABEL_PATH[biography.specialty_code] ?? 'specialty.none',
                ),
              },
              {
                entryKey: 'dragon-kill',
                labelText: label('biography.field.dragon-kill'),
                valueSlot: biography.dragon_kill_count,
              },
              {
                entryKey: 'resurrection',
                labelText: label('biography.field.resurrection'),
                valueSlot: biography.resurrection_count,
              },
              {
                entryKey: 'mount',
                labelText: label('biography.field.mount'),
                valueSlot: biography.mount_name ?? label('biography.no-mount'),
              },
            ],
          },
        ]

  return (
    <LotdgScreen titleText={label('biography.title')}>
      <LotdgNoticeLine messageText={errorMessage} tone={LOTDG_NOTICE_TONE.FAILURE} />

      {biography !== null && (
        <>
          <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_WHITE}>
            {biography.rank_title} {biography.display_name}
          </LotdgText>

          <LotdgStatTable sectionList={profileSectionList} />

          <LotdgSection titleSlot={label('biography.body-title')}>
            {biography.biography === '' ? (
              <LotdgText>{label('biography.body-empty')}</LotdgText>
            ) : (
              <LotdgMarkupText sourceText={biography.biography} />
            )}
          </LotdgSection>

          <LotdgSection titleSlot={label('biography.news-title')}>
            {biography.news_history.length === 0 ? (
              <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
                {label('biography.news-empty')}
              </LotdgText>
            ) : (
              biography.news_history.map((news) => (
                <LotdgText key={news.news_id}>
                  <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
                    {news.news_date}
                  </LotdgInlineText>{' '}
                  {parseLegacyMarkup(resolveNewsText(news.news_text, translate))}
                </LotdgText>
              ))
            )}
          </LotdgSection>
        </>
      )}
    </LotdgScreen>
  )
}
