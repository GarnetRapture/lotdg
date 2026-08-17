import { useContext } from 'react'
import {
  LotdgSessionContext,
  type LotdgSessionContextValue,
} from './lotdg-session-context-definition'

export function useLotdgSession(): LotdgSessionContextValue {
  const contextValue = useContext(LotdgSessionContext)

  if (contextValue === null) {
    throw new Error('LotdgSessionProvider 안에서만 사용할 수 있습니다.')
  }

  return contextValue
}
