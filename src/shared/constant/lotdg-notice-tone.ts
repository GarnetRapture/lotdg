export const LOTDG_NOTICE_TONE = {
  INFORMATION: 'colLtYellow',
  SUCCESS: 'colLtGreen',
  FAILURE: 'colLtRed',
} as const

export type LotdgNoticeTone = (typeof LOTDG_NOTICE_TONE)[keyof typeof LOTDG_NOTICE_TONE]
