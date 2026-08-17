import { useCallback, useMemo, useState, type ReactNode } from 'react'
import {
  LOTDG_FALLBACK_LOCALE_CODE,
  type LotdgLocaleNamespace,
  type LotdgSupportedLocaleCode,
} from '../shared/constant/lotdg-supported-locale'
import { formatLabel, loadLocaleBundle } from './lotdg-locale-resource-loader'
import type { LotdgLocaleResource } from '../shared/schema/system/lotdg-localization-schema'
import { LotdgLocaleContext, type LotdgLocaleContextValue } from './lotdg-locale-context-definition'

export function LotdgLocaleProvider({ children }: { readonly children: ReactNode }) {
  const [localeCode, setLocaleCode] = useState<LotdgSupportedLocaleCode>(LOTDG_FALLBACK_LOCALE_CODE)

  const bundle = useMemo<Record<string, LotdgLocaleResource>>(
    () => loadLocaleBundle(localeCode),
    [localeCode],
  )

  const translate = useCallback(
    (
      namespace: LotdgLocaleNamespace,
      labelPath: string,
      valueMap: Record<string, string | number> = {},
    ): string => {
      const template = bundle[namespace]?.[labelPath]

      if (template === undefined) {
        return labelPath
      }

      return formatLabel(template, valueMap)
    },
    [bundle],
  )

  const contextValue = useMemo<LotdgLocaleContextValue>(
    () => ({ localeCode, setLocaleCode, translate }),
    [localeCode, translate],
  )

  return <LotdgLocaleContext value={contextValue}>{children}</LotdgLocaleContext>
}
