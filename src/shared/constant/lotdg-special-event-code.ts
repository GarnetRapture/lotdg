export const LOTDG_SPECIAL_EVENT_CODE = {
  FIND_GEM: 'findgem',
  FIND_GOLD: 'findgold',
  OLD_MAN_PRETTY: 'oldmanpretty',
  OLD_MAN_UGLY: 'oldmanugly',
  OLD_MAN_BET: 'oldmanbet',
  OLD_MAN_TOWN: 'oldmantown',
  GLOWING_STREAM: 'glowingstream',
  FAIRY: 'fairy1',
  GRASSY_FIELD: 'grassyfield',
  RIDDLES: 'riddles',
  AUDREY: 'audrey',
  GOLD_MINE: 'goldmine',
  SKILL_MASTER: 'skillmaster',
  DISTRESS: 'distress',
  NECROMANCER: 'necromancer',
  DARK_HORSE: 'darkhorse',
} as const

export type LotdgSpecialEventCode =
  (typeof LOTDG_SPECIAL_EVENT_CODE)[keyof typeof LOTDG_SPECIAL_EVENT_CODE]

export const LOTDG_INSTANT_SPECIAL_EVENT_CODE_LIST: readonly LotdgSpecialEventCode[] = [
  LOTDG_SPECIAL_EVENT_CODE.FIND_GEM,
  LOTDG_SPECIAL_EVENT_CODE.FIND_GOLD,
  LOTDG_SPECIAL_EVENT_CODE.OLD_MAN_PRETTY,
  LOTDG_SPECIAL_EVENT_CODE.OLD_MAN_UGLY,
]

export const LOTDG_SPECIAL_EVENT_CODE_LIST = Object.values(
  LOTDG_SPECIAL_EVENT_CODE,
) as readonly LotdgSpecialEventCode[]
