export const LOTDG_COMMENTARY_SECTION_CODE = {
  VILLAGE: 'village',
  INN: 'inn',
  HEALER: 'healer',
  MOUNT_STABLE: 'stables',
  GEM_TRADER: 'gemtrader',
  BOUNTY: 'dag',
} as const

export type LotdgCommentarySectionCode =
  (typeof LOTDG_COMMENTARY_SECTION_CODE)[keyof typeof LOTDG_COMMENTARY_SECTION_CODE]

export const LOTDG_COMMENT_MAXIMUM_LENGTH = 200
