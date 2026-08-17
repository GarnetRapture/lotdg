export const LOTDG_SUPPORTED_LOCALE_CODE = {
  ENGLISH: 'en',
  KOREAN: 'ko',
  JAPANESE: 'ja',
  CHINESE_SIMPLIFIED: 'zh',
  RUSSIAN: 'ru',
} as const

export type LotdgSupportedLocaleCode =
  (typeof LOTDG_SUPPORTED_LOCALE_CODE)[keyof typeof LOTDG_SUPPORTED_LOCALE_CODE]

export const LOTDG_SUPPORTED_LOCALE_ENDONYM: Record<LotdgSupportedLocaleCode, string> = {
  en: 'English',
  ko: '한국어',
  ja: '日本語',
  zh: '简体中文',
  ru: 'Русский',
}

export const LOTDG_FALLBACK_LOCALE_CODE: LotdgSupportedLocaleCode =
  LOTDG_SUPPORTED_LOCALE_CODE.ENGLISH

export const LOTDG_LOCALE_NAMESPACE = {
  COMMON: 'common',
  CHARACTER_STAT: 'character-stat',
  NAVIGATION: 'navigation',
  AUTHENTICATION: 'authentication',
  VILLAGE: 'village',
  FOREST: 'forest',
  BATTLE: 'battle',
  COMMERCE: 'commerce',
  SOCIAL: 'social',
  SYSTEM_MESSAGE: 'system-message',
} as const

export type LotdgLocaleNamespace =
  (typeof LOTDG_LOCALE_NAMESPACE)[keyof typeof LOTDG_LOCALE_NAMESPACE]

export const LOTDG_SUPPORTED_LOCALE_CODE_LIST = Object.values(
  LOTDG_SUPPORTED_LOCALE_CODE,
) as readonly LotdgSupportedLocaleCode[]

export const LOTDG_LOCALE_NAMESPACE_LIST = Object.values(
  LOTDG_LOCALE_NAMESPACE,
) as readonly LotdgLocaleNamespace[]
