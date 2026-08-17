import {
  LOTDG_FALLBACK_LOCALE_CODE,
  LOTDG_LOCALE_NAMESPACE,
  type LotdgLocaleNamespace,
  type LotdgSupportedLocaleCode,
} from '../shared/constant/lotdg-supported-locale'
import {
  lotdgLocaleResourceSchema,
  type LotdgLocaleResource,
} from '../shared/schema/system/lotdg-localization-schema'

const RESOURCE_MODULE_MAP = import.meta.glob('./locale/*/*.json', { eager: true }) as Record<
  string,
  { default: unknown }
>

function buildModuleKey(localeCode: string, namespace: string): string {
  return `./locale/${localeCode}/${namespace}.json`
}

function readResource(localeCode: string, namespace: string): LotdgLocaleResource {
  const moduleEntry = RESOURCE_MODULE_MAP[buildModuleKey(localeCode, namespace)]

  if (moduleEntry === undefined) {
    return {}
  }

  const parsed = lotdgLocaleResourceSchema.safeParse(moduleEntry.default)

  return parsed.success ? parsed.data : {}
}

export function loadLocaleNamespace(
  localeCode: LotdgSupportedLocaleCode,
  namespace: LotdgLocaleNamespace,
): LotdgLocaleResource {
  const fallbackResource = readResource(LOTDG_FALLBACK_LOCALE_CODE, namespace)
  const requestedResource = readResource(localeCode, namespace)

  return { ...fallbackResource, ...requestedResource }
}

export function loadLocaleBundle(
  localeCode: LotdgSupportedLocaleCode,
): Record<string, LotdgLocaleResource> {
  const bundle: Record<string, LotdgLocaleResource> = {}

  for (const namespace of Object.values(LOTDG_LOCALE_NAMESPACE)) {
    bundle[namespace] = loadLocaleNamespace(localeCode, namespace)
  }

  return bundle
}

export function formatLabel(template: string, valueMap: Record<string, string | number>): string {
  return template.replace(/\{([a-z0-9-]+)\}/gi, (match, placeholderName: string) => {
    const value = valueMap[placeholderName]

    return value === undefined ? match : String(value)
  })
}
