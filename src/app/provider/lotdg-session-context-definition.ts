import { createContext } from 'react'

export interface LotdgSessionState {
  readonly accountId: number
  readonly characterId: number
  readonly loginName: string
  readonly superuserLevel: number
  readonly storedLocaleCode: string | undefined
}

export interface LotdgSessionContextValue {
  readonly session: LotdgSessionState | null
  readonly signIn: (session: LotdgSessionState) => void
  readonly signOut: () => void
}

export const LotdgSessionContext = createContext<LotdgSessionContextValue | null>(null)
