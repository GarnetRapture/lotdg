import { useCallback } from 'react'
import { postForm } from '../shared/lib/lotdg-api-client'
import { lotdgPreferenceLocaleSchema } from '../shared/schema/account/lotdg-account-schema'
import {
  LOTDG_SUPPORTED_LOCALE_CODE_LIST,
  type LotdgSupportedLocaleCode,
} from '../shared/constant/lotdg-supported-locale'
import { useLotdgLocale } from './useLotdgLocale'

export function isSupportedLocaleCode(value: string): value is LotdgSupportedLocaleCode {
  return (LOTDG_SUPPORTED_LOCALE_CODE_LIST as readonly string[]).includes(value)
}

/**
 * 언어 선택을 account_preference.locale_code 와 맞춘다. 로그인 응답이 실어 오는
 * 저장값을 적용하고, 변경은 즉시 화면에 반영한 뒤 같은 값을 서버에 기록한다.
 */
export function useLotdgLocalePersistence(characterId: number | null) {
  const { localeCode, setLocaleCode } = useLotdgLocale()

  const applyStoredLocaleCode = useCallback(
    (storedLocaleCode: string | undefined) => {
      if (storedLocaleCode !== undefined && isSupportedLocaleCode(storedLocaleCode)) {
        setLocaleCode(storedLocaleCode)
      }
    },
    [setLocaleCode],
  )

  const changeLocaleCode = useCallback(
    (nextLocaleCode: LotdgSupportedLocaleCode) => {
      setLocaleCode(nextLocaleCode)

      if (characterId === null) {
        return
      }

      void postForm(`/preference/${characterId}/locale`, lotdgPreferenceLocaleSchema, {
        locale_code: nextLocaleCode,
      }).catch(() => undefined)
    },
    [characterId, setLocaleCode],
  )

  return { localeCode, applyStoredLocaleCode, changeLocaleCode }
}
