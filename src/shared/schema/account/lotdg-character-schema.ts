import { z } from 'zod'
import {
  lotdgIdentifierSchema,
  lotdgJsonArraySchema,
  lotdgJsonObjectSchema,
  lotdgNonNegativeIntegerSchema,
  lotdgSqliteBooleanSchema,
  lotdgSqliteDateSchema,
  lotdgSqliteDateTimeSchema,
} from '../system/lotdg-sqlite-primitive-schema'

export const lotdgSexCodeSchema = z.union([z.literal(0), z.literal(1)])

export const lotdgRaceCodeSchema = z.union([
  z.literal(0),
  z.literal(1),
  z.literal(2),
  z.literal(3),
  z.literal(4),
  z.literal(50),
])

export const lotdgLocationCodeSchema = z.union([z.literal(0), z.literal(1)])

export const lotdgSpiritLevelSchema = z.union([
  z.literal(-6),
  z.literal(-2),
  z.literal(-1),
  z.literal(0),
  z.literal(1),
  z.literal(2),
])

export const lotdgSpecialtyCodeSchema = z.union([
  z.literal(0),
  z.literal(1),
  z.literal(2),
  z.literal(3),
])

export const lotdgGameCharacterSchema = z.object({
  character_id: lotdgIdentifierSchema,
  account_id: lotdgIdentifierSchema,
  display_name: z.string(),
  sex_code: lotdgSexCodeSchema,
  race_code: lotdgRaceCodeSchema,
  level: z.int().min(1),
  rank_title: z.string(),
  custom_title: z.string(),
  location_code: lotdgLocationCodeSchema,
  restore_page_uri: z.string(),
})

export const lotdgCharacterVitalSchema = z.object({
  character_id: lotdgIdentifierSchema,
  hit_point: z.int(),
  max_hit_point: lotdgNonNegativeIntegerSchema,
  is_alive: lotdgSqliteBooleanSchema,
  spirit_level: lotdgSpiritLevelSchema,
  soul_point: lotdgNonNegativeIntegerSchema,
  grave_fight: lotdgNonNegativeIntegerSchema,
  death_power: lotdgNonNegativeIntegerSchema,
  haunt_point: lotdgNonNegativeIntegerSchema,
  haunted_by_name: z.string(),
  slain_by_name: z.string(),
  killed_in_area: z.string(),
  resurrection_count: lotdgNonNegativeIntegerSchema,
})

export const lotdgCharacterCombatStatSchema = z.object({
  character_id: lotdgIdentifierSchema,
  attack_point: lotdgNonNegativeIntegerSchema,
  defence_point: lotdgNonNegativeIntegerSchema,
  buff_list_json: lotdgJsonObjectSchema,
  buff_backup_json: lotdgJsonObjectSchema,
  current_enemy_json: lotdgJsonObjectSchema,
})

export const lotdgCharacterProgressionSchema = z.object({
  character_id: lotdgIdentifierSchema,
  experience: lotdgNonNegativeIntegerSchema,
  dragon_kill_count: lotdgNonNegativeIntegerSchema,
  dragon_point_json: lotdgJsonObjectSchema,
  game_age_day: lotdgNonNegativeIntegerSchema,
  dragon_age_day: lotdgNonNegativeIntegerSchema,
  best_dragon_age_day: lotdgNonNegativeIntegerSchema,
  has_seen_dragon: lotdgSqliteBooleanSchema,
  seen_master_level: lotdgNonNegativeIntegerSchema,
  has_seen_bard: lotdgSqliteBooleanSchema,
  has_seen_lover: lotdgSqliteBooleanSchema,
})

export const lotdgCharacterSpecialtySchema = z.object({
  character_id: lotdgIdentifierSchema,
  specialty_code: lotdgSpecialtyCodeSchema,
  dark_arts_rank: lotdgNonNegativeIntegerSchema,
  mystical_power_rank: lotdgNonNegativeIntegerSchema,
  thievery_rank: lotdgNonNegativeIntegerSchema,
  dark_arts_use: lotdgNonNegativeIntegerSchema,
  mystical_power_use: lotdgNonNegativeIntegerSchema,
  thievery_use: lotdgNonNegativeIntegerSchema,
})

export const lotdgCharacterEquipmentSchema = z.object({
  character_id: lotdgIdentifierSchema,
  weapon_id: lotdgIdentifierSchema.nullable(),
  weapon_name: z.string(),
  weapon_value: lotdgNonNegativeIntegerSchema,
  weapon_damage: z.int(),
  armor_id: lotdgIdentifierSchema.nullable(),
  armor_name: z.string(),
  armor_value: lotdgNonNegativeIntegerSchema,
  armor_defense: z.int(),
  mount_id: lotdgNonNegativeIntegerSchema,
})

export const lotdgCharacterWealthSchema = z.object({
  character_id: lotdgIdentifierSchema,
  gold: lotdgNonNegativeIntegerSchema,
  gold_in_bank: z.int(),
  gem: lotdgNonNegativeIntegerSchema,
  bounty_on_self: lotdgNonNegativeIntegerSchema,
  bounty_set_today: lotdgNonNegativeIntegerSchema,
  received_today: lotdgNonNegativeIntegerSchema,
  transferred_today: lotdgNonNegativeIntegerSchema,
})

export const lotdgCharacterDailyAllowanceSchema = z.object({
  character_id: lotdgIdentifierSchema,
  forest_turn: lotdgNonNegativeIntegerSchema,
  player_fight: lotdgNonNegativeIntegerSchema,
  drunkenness: lotdgNonNegativeIntegerSchema,
  bought_room_today: lotdgSqliteBooleanSchema,
  used_outhouse_today: lotdgSqliteBooleanSchema,
  last_web_vote_date: lotdgSqliteDateSchema.nullable(),
  last_motd_seen_at: lotdgSqliteDateTimeSchema.nullable(),
})

export const lotdgCharacterSocialSchema = z.object({
  character_id: lotdgIdentifierSchema,
  married_to_character_id: lotdgIdentifierSchema.nullable(),
  player_kill_count: lotdgNonNegativeIntegerSchema,
  pvp_immunity_lost: lotdgSqliteBooleanSchema,
  charm: lotdgNonNegativeIntegerSchema,
  charisma: lotdgNonNegativeIntegerSchema,
  biography: z.string(),
  biography_updated_at: lotdgSqliteDateTimeSchema.nullable(),
  history_json: lotdgJsonArraySchema,
  comments_seen_at: lotdgSqliteDateTimeSchema.nullable(),
  pvp_flag_at: lotdgSqliteDateTimeSchema.nullable(),
})

export const lotdgCharacterBundleSchema = z.object({
  character: lotdgGameCharacterSchema,
  vital: lotdgCharacterVitalSchema,
  combat_stat: lotdgCharacterCombatStatSchema,
  progression: lotdgCharacterProgressionSchema,
  specialty: lotdgCharacterSpecialtySchema,
  equipment: lotdgCharacterEquipmentSchema,
  wealth: lotdgCharacterWealthSchema,
  daily_allowance: lotdgCharacterDailyAllowanceSchema,
})

export type LotdgGameCharacter = z.infer<typeof lotdgGameCharacterSchema>
export type LotdgCharacterVital = z.infer<typeof lotdgCharacterVitalSchema>
export type LotdgCharacterCombatStat = z.infer<typeof lotdgCharacterCombatStatSchema>
export type LotdgCharacterProgression = z.infer<typeof lotdgCharacterProgressionSchema>
export type LotdgCharacterSpecialty = z.infer<typeof lotdgCharacterSpecialtySchema>
export type LotdgCharacterEquipment = z.infer<typeof lotdgCharacterEquipmentSchema>
export type LotdgCharacterWealth = z.infer<typeof lotdgCharacterWealthSchema>
export type LotdgCharacterDailyAllowance = z.infer<typeof lotdgCharacterDailyAllowanceSchema>
export type LotdgCharacterSocial = z.infer<typeof lotdgCharacterSocialSchema>
export type LotdgCharacterBundle = z.infer<typeof lotdgCharacterBundleSchema>
