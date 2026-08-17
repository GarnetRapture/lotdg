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
