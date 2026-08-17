import { createContext } from 'react'
import type { LotdgThemeCode } from '../../shared/constant/lotdg-theme-code'

export interface LotdgThemeContextValue {
  readonly themeCode: LotdgThemeCode
  readonly changeThemeCode: (nextThemeCode: LotdgThemeCode) => void
}

export const LotdgThemeContext = createContext<LotdgThemeContextValue | null>(null)
