export const LOTDG_THEME_CODE = {
  LEGACY: 'legacy',
  MODERN: 'modern',
} as const

export type LotdgThemeCode = (typeof LOTDG_THEME_CODE)[keyof typeof LOTDG_THEME_CODE]

export const LOTDG_THEME_CODE_LIST = Object.values(LOTDG_THEME_CODE) as readonly LotdgThemeCode[]

export const LOTDG_FALLBACK_THEME_CODE: LotdgThemeCode = LOTDG_THEME_CODE.LEGACY

export const LOTDG_THEME_DOCUMENT_ATTRIBUTE_NAME = 'data-lotdg-theme' as const

export const LOTDG_THEME_STORAGE_KEY = 'lotdg.theme-code' as const

export const LOTDG_THEME_LABEL_PATH: Record<LotdgThemeCode, string> = {
  legacy: 'theme.legacy',
  modern: 'theme.modern',
}
