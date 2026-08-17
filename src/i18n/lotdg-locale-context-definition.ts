import { createContext } from 'react'
import type {
  LotdgLocaleNamespace,
  LotdgSupportedLocaleCode,
} from '../shared/constant/lotdg-supported-locale'

export interface LotdgLocaleContextValue {
  readonly localeCode: LotdgSupportedLocaleCode
  readonly setLocaleCode: (localeCode: LotdgSupportedLocaleCode) => void
  readonly translate: (
    namespace: LotdgLocaleNamespace,
    labelPath: string,
    valueMap?: Record<string, string | number>,
  ) => string
}

export const LotdgLocaleContext = createContext<LotdgLocaleContextValue | null>(null)
