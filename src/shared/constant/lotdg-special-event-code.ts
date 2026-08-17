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

export const LOTDG_SPECIAL_EVENT_STAGE_CODE = {
  OUTSIDE: 'outside',
  AWAITING_CHOICE: 'awaiting-choice',
  AWAITING_BET: 'awaiting-bet',
  AWAITING_GUESS: 'awaiting-guess',
  AWAITING_ANSWER: 'awaiting-answer',
  AWAITING_GEM: 'awaiting-gem',
  RESTING: 'resting',
} as const

export type LotdgSpecialEventStageCode =
  (typeof LOTDG_SPECIAL_EVENT_STAGE_CODE)[keyof typeof LOTDG_SPECIAL_EVENT_STAGE_CODE]

export const LOTDG_SPECIAL_EVENT_ACTION_CODE = {
  START: 'start',
  BET: 'bet',
  GUESS: 'guess',
  ANSWER: 'answer',
  POST: 'post',
  VISIT: 'visit',
  IGNORE: 'ignore',
  DRINK: 'drink',
  GIVE: 'give',
  ACCEPT: 'accept',
  PLAY: 'play',
  MINE: 'mine',
  APPROACH: 'approach',
  ESCORT: 'escort',
  ENTER: 'enter',
  REFUSE: 'refuse',
  RUN: 'run',
  LEAVE: 'leave',
  DECLINE: 'decline',
  GIVE_GEM: 'give-gem',
  KEEP_GEM: 'keep-gem',
} as const

export type LotdgSpecialEventActionCode =
  (typeof LOTDG_SPECIAL_EVENT_ACTION_CODE)[keyof typeof LOTDG_SPECIAL_EVENT_ACTION_CODE]

export const LOTDG_DARK_HORSE_STAGE_CODE = {
  OUTSIDE: 'outside',
  INSIDE: 'inside',
  DICE_GAME: 'dice-game',
  DICE_SETTLED: 'dice-settled',
  STONE_GAME: 'stone-game',
  STONE_SETTLED: 'stone-settled',
  ETCHING: 'etching',
  LEFT: 'left',
} as const

export type LotdgDarkHorseStageCode =
  (typeof LOTDG_DARK_HORSE_STAGE_CODE)[keyof typeof LOTDG_DARK_HORSE_STAGE_CODE]

export const LOTDG_DARK_HORSE_ACTION_CODE = {
  ENTER: 'enter',
  LEAVE: 'leave',
  ETCHING: 'etching',
  DICE_START: 'dice-start',
  DICE_REROLL: 'dice-reroll',
  DICE_KEEP: 'dice-keep',
  STONE_START: 'stone-start',
  STONE_DRAW: 'stone-draw',
  ENEMY_INSPECT: 'enemy-inspect',
} as const

export type LotdgDarkHorseActionCode =
  (typeof LOTDG_DARK_HORSE_ACTION_CODE)[keyof typeof LOTDG_DARK_HORSE_ACTION_CODE]

export const LOTDG_DARK_HORSE_STONE_SIDE_CODE = {
  LIKE_PAIR: 'likepair',
  UNLIKE_PAIR: 'unlikepair',
} as const

export type LotdgDarkHorseStoneSideCode =
  (typeof LOTDG_DARK_HORSE_STONE_SIDE_CODE)[keyof typeof LOTDG_DARK_HORSE_STONE_SIDE_CODE]

export const LOTDG_DARK_HORSE_STONE_SIDE_CODE_LIST = Object.values(
  LOTDG_DARK_HORSE_STONE_SIDE_CODE,
) as readonly LotdgDarkHorseStoneSideCode[]

export const LOTDG_DARK_HORSE_DRAW_OUTCOME_CODE = 'draw' as const

export const LOTDG_SPECIAL_EVENT_ACCEPT_ACTION_CODE: Record<
  LotdgSpecialEventCode,
  LotdgSpecialEventActionCode
> = {
  findgem: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  findgold: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  oldmanpretty: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  oldmanugly: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  oldmanbet: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  oldmantown: LOTDG_SPECIAL_EVENT_ACTION_CODE.ESCORT,
  glowingstream: LOTDG_SPECIAL_EVENT_ACTION_CODE.DRINK,
  fairy1: LOTDG_SPECIAL_EVENT_ACTION_CODE.GIVE,
  grassyfield: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  riddles: LOTDG_SPECIAL_EVENT_ACTION_CODE.ACCEPT,
  audrey: LOTDG_SPECIAL_EVENT_ACTION_CODE.PLAY,
  goldmine: LOTDG_SPECIAL_EVENT_ACTION_CODE.MINE,
  skillmaster: LOTDG_SPECIAL_EVENT_ACTION_CODE.GIVE,
  distress: LOTDG_SPECIAL_EVENT_ACTION_CODE.START,
  necromancer: LOTDG_SPECIAL_EVENT_ACTION_CODE.APPROACH,
  darkhorse: LOTDG_SPECIAL_EVENT_ACTION_CODE.ENTER,
}

export const LOTDG_SPECIAL_EVENT_DECLINE_ACTION_CODE: Record<
  LotdgSpecialEventCode,
  LotdgSpecialEventActionCode
> = {
  findgem: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  findgold: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  oldmanpretty: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  oldmanugly: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  oldmanbet: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  oldmantown: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  glowingstream: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  fairy1: LOTDG_SPECIAL_EVENT_ACTION_CODE.REFUSE,
  grassyfield: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  riddles: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  audrey: LOTDG_SPECIAL_EVENT_ACTION_CODE.RUN,
  goldmine: LOTDG_SPECIAL_EVENT_ACTION_CODE.DECLINE,
  skillmaster: LOTDG_SPECIAL_EVENT_ACTION_CODE.REFUSE,
  distress: LOTDG_SPECIAL_EVENT_ACTION_CODE.IGNORE,
  necromancer: LOTDG_SPECIAL_EVENT_ACTION_CODE.LEAVE,
  darkhorse: LOTDG_SPECIAL_EVENT_ACTION_CODE.LEAVE,
}
