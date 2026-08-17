import { LotdgApiError } from './lotdg-api-client'
import {
  LOTDG_LOCALE_NAMESPACE,
  type LotdgLocaleNamespace,
} from '../constant/lotdg-supported-locale'

const NAMESPACE_LIST = Object.values(LOTDG_LOCALE_NAMESPACE) as readonly string[]

function isKnownNamespace(namespaceCode: string): namespaceCode is LotdgLocaleNamespace {
  return NAMESPACE_LIST.includes(namespaceCode)
}

export function resolveErrorLabel(
  error: unknown,
  translate: (
    namespace: LotdgLocaleNamespace,
    labelPath: string,
    valueMap?: Record<string, string | number>,
  ) => string,
): string {
  if (error instanceof LotdgApiError) {
    const namespaceCode = isKnownNamespace(error.namespaceCode)
      ? error.namespaceCode
      : LOTDG_LOCALE_NAMESPACE.SYSTEM_MESSAGE

    return translate(namespaceCode, error.labelPath, error.placeholderMap)
  }

  return translate(LOTDG_LOCALE_NAMESPACE.SYSTEM_MESSAGE, 'error.unknown')
}

export function resolveMessageKeyLabel(
  messageKey: string | undefined,
  translate: (
    namespace: LotdgLocaleNamespace,
    labelPath: string,
    valueMap?: Record<string, string | number>,
  ) => string,
  valueMap: Record<string, string | number> = {},
): string {
  if (messageKey === undefined || messageKey === '') {
    return translate(LOTDG_LOCALE_NAMESPACE.SYSTEM_MESSAGE, 'error.unknown')
  }

  const separatorIndex = messageKey.indexOf('.')

  if (separatorIndex < 0) {
    return translate(LOTDG_LOCALE_NAMESPACE.SYSTEM_MESSAGE, messageKey, valueMap)
  }

  const namespaceCode = messageKey.slice(0, separatorIndex)
  const labelPath = messageKey.slice(separatorIndex + 1)

  if (isKnownNamespace(namespaceCode)) {
    return translate(namespaceCode, labelPath, valueMap)
  }

  return translate(LOTDG_LOCALE_NAMESPACE.SYSTEM_MESSAGE, messageKey, valueMap)
}
