import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  LOTDG_FALLBACK_THEME_CODE,
  LOTDG_THEME_CODE_LIST,
  LOTDG_THEME_DOCUMENT_ATTRIBUTE_NAME,
  LOTDG_THEME_STORAGE_KEY,
  type LotdgThemeCode,
} from '../../shared/constant/lotdg-theme-code'
import { readLocalStorageText, writeLocalStorageText } from '../../shared/lib/lotdg-local-storage'
import type { LotdgChildrenProps } from '../../shared/type/lotdg-ui-component-contract'
import { LotdgThemeContext, type LotdgThemeContextValue } from './lotdg-theme-context-definition'

function isThemeCode(value: string | null): value is LotdgThemeCode {
  return value !== null && (LOTDG_THEME_CODE_LIST as readonly string[]).includes(value)
}

function resolveInitialThemeCode(): LotdgThemeCode {
  const storedThemeCode = readLocalStorageText(LOTDG_THEME_STORAGE_KEY)

  return isThemeCode(storedThemeCode) ? storedThemeCode : LOTDG_FALLBACK_THEME_CODE
}

export function LotdgThemeProvider({ children }: LotdgChildrenProps) {
  const [themeCode, setThemeCode] = useState<LotdgThemeCode>(resolveInitialThemeCode)

  useEffect(() => {
    document.documentElement.setAttribute(LOTDG_THEME_DOCUMENT_ATTRIBUTE_NAME, themeCode)
  }, [themeCode])

  const changeThemeCode = useCallback((nextThemeCode: LotdgThemeCode) => {
    setThemeCode(nextThemeCode)
    writeLocalStorageText(LOTDG_THEME_STORAGE_KEY, nextThemeCode)
  }, [])

  const contextValue = useMemo<LotdgThemeContextValue>(
    () => ({ themeCode, changeThemeCode }),
    [themeCode, changeThemeCode],
  )

  return <LotdgThemeContext value={contextValue}>{children}</LotdgThemeContext>
}
