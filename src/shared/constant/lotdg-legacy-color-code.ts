export const LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME = {
  '1': 'colDkBlue',
  '2': 'colDkGreen',
  '3': 'colDkCyan',
  '4': 'colDkRed',
  '5': 'colDkMagenta',
  '6': 'colDkYellow',
  '7': 'colDkWhite',
  '!': 'colLtBlue',
  '@': 'colLtGreen',
  '#': 'colLtCyan',
  $: 'colLtRed',
  '%': 'colLtMagenta',
  '^': 'colLtYellow',
  '&': 'colLtWhite',
  ')': 'colLtBlack',
} as const

export const LOTDG_LEGACY_COLOR_RESET_CODE = '0' as const

export const LOTDG_LEGACY_FORMAT_CODE = {
  CENTER_TOGGLE: 'c',
  NAVIGATION_HOTKEY_TOGGLE: 'H',
  BOLD_TOGGLE: 'b',
  ITALIC_TOGGLE: 'i',
  LINE_BREAK: 'n',
  CURRENT_WEAPON_NAME: 'w',
  LITERAL_BACKTICK: '`',
} as const

export const LOTDG_LEGACY_ESCAPE_CHARACTER = '`' as const

export type LotdgLegacyColorCode = keyof typeof LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME
export type LotdgLegacyColorClassName =
  (typeof LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME)[LotdgLegacyColorCode]
export type LotdgLegacyFormatCode =
  (typeof LOTDG_LEGACY_FORMAT_CODE)[keyof typeof LOTDG_LEGACY_FORMAT_CODE]
