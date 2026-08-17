export const LOTDG_SEX_CODE = { MALE: 0, FEMALE: 1 } as const

export type LotdgSexCode = (typeof LOTDG_SEX_CODE)[keyof typeof LOTDG_SEX_CODE]

export const LOTDG_RACE_CODE = {
  UNKNOWN: 0,
  TROLL: 1,
  ELF: 2,
  HUMAN: 3,
  DWARF: 4,
  HOVERSHEEP: 50,
} as const

export type LotdgRaceCode = (typeof LOTDG_RACE_CODE)[keyof typeof LOTDG_RACE_CODE]

export const LOTDG_RACE_LABEL_PATH: Record<number, string> = {
  0: 'race.unknown',
  1: 'race.troll',
  2: 'race.elf',
  3: 'race.human',
  4: 'race.dwarf',
  50: 'race.hoversheep',
}

export const LOTDG_SPECIALTY_CODE = {
  NONE: 0,
  DARK_ARTS: 1,
  MYSTICAL_POWER: 2,
  THIEVERY: 3,
} as const

export type LotdgSpecialtyCode = (typeof LOTDG_SPECIALTY_CODE)[keyof typeof LOTDG_SPECIALTY_CODE]

export const LOTDG_SPECIALTY_LABEL_PATH: Record<number, string> = {
  0: 'specialty.none',
  1: 'specialty.dark-arts',
  2: 'specialty.mystical-power',
  3: 'specialty.thievery',
}

export const LOTDG_LOCATION_CODE = { FIELD: 0, INN: 1 } as const

export type LotdgLocationCode = (typeof LOTDG_LOCATION_CODE)[keyof typeof LOTDG_LOCATION_CODE]

export const LOTDG_LOCATION_LABEL_PATH: Record<number, string> = {
  0: 'location.field',
  1: 'location.inn',
}

export const LOTDG_TOILET_TYPE = { PRIVATE: 'private', PUBLIC: 'public' } as const

export type LotdgToiletType = (typeof LOTDG_TOILET_TYPE)[keyof typeof LOTDG_TOILET_TYPE]

export const LOTDG_SOCIAL_VENUE_CODE = { GARDENS: 'gardens', VETERANS: 'veterans' } as const

export type LotdgSocialVenueCode =
  (typeof LOTDG_SOCIAL_VENUE_CODE)[keyof typeof LOTDG_SOCIAL_VENUE_CODE]

export const LOTDG_SHOP_TYPE_CODE = { WEAPON: 'weapon', ARMOR: 'armor' } as const

export type LotdgShopTypeCode = (typeof LOTDG_SHOP_TYPE_CODE)[keyof typeof LOTDG_SHOP_TYPE_CODE]

export const LOTDG_MOTD_TYPE = { NOTICE: 0, POLL: 1 } as const

export type LotdgMotdType = (typeof LOTDG_MOTD_TYPE)[keyof typeof LOTDG_MOTD_TYPE]
