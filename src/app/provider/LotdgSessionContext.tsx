import { useCallback, useMemo, useState, type ReactNode } from 'react'
import {
  LotdgSessionContext,
  type LotdgSessionContextValue,
  type LotdgSessionState,
} from './lotdg-session-context-definition'

export function LotdgSessionProvider({ children }: { readonly children: ReactNode }) {
  const [session, setSession] = useState<LotdgSessionState | null>(null)

  const signIn = useCallback((nextSession: LotdgSessionState) => {
    setSession(nextSession)
  }, [])

  const signOut = useCallback(() => {
    setSession(null)
  }, [])

  const contextValue = useMemo<LotdgSessionContextValue>(
    () => ({ session, signIn, signOut }),
    [session, signIn, signOut],
  )

  return <LotdgSessionContext value={contextValue}>{children}</LotdgSessionContext>
}
