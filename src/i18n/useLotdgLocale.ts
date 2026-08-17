import { useContext } from 'react'
import { LotdgLocaleContext, type LotdgLocaleContextValue } from './lotdg-locale-context-definition'

export function useLotdgLocale(): LotdgLocaleContextValue {
  const contextValue = useContext(LotdgLocaleContext)

  if (contextValue === null) {
    throw new Error('LotdgLocaleProvider 안에서만 사용할 수 있습니다.')
  }

  return contextValue
}
