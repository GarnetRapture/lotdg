import { useContext } from 'react'
import { LotdgThemeContext, type LotdgThemeContextValue } from './lotdg-theme-context-definition'

export function useLotdgTheme(): LotdgThemeContextValue {
  const contextValue = useContext(LotdgThemeContext)

  if (contextValue === null) {
    throw new Error('LotdgThemeProvider 안에서만 사용할 수 있습니다.')
  }

  return contextValue
}
