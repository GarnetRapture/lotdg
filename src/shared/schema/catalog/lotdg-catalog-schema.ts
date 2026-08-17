import { z } from 'zod'
import {
  lotdgIdentifierSchema,
  lotdgJsonObjectSchema,
  lotdgNonNegativeIntegerSchema,
  lotdgSqliteBooleanSchema,
} from '../system/lotdg-sqlite-primitive-schema'

export const lotdgWeaponSchema = z.object({
  weapon_id: lotdgIdentifierSchema,
  weapon_name: z.string().min(1),
  price: lotdgNonNegativeIntegerSchema,
  damage: z.int(),
  dragon_kill_tier: lotdgNonNegativeIntegerSchema,
})

export const lotdgArmorSchema = z.object({
  armor_id: lotdgIdentifierSchema,
  armor_name: z.string().min(1),
  price: lotdgNonNegativeIntegerSchema,
  defense: z.int(),
  dragon_kill_tier: lotdgNonNegativeIntegerSchema,
})

export const lotdgCreatureSchema = z.object({
  creature_id: lotdgIdentifierSchema,
  creature_name: z.string().min(1),
  creature_level: z.int().min(1),
  weapon_name: z.string(),
  victory_message: z.string(),
  defeat_message: z.string(),
  gold_reward: lotdgNonNegativeIntegerSchema,
  experience_reward: lotdgNonNegativeIntegerSchema,
  health: z.int().min(1),
  attack_point: lotdgNonNegativeIntegerSchema,
  defense_point: lotdgNonNegativeIntegerSchema,
  location_code: z.union([z.literal(0), z.literal(1)]),
  created_by_name: z.string(),
})

export const lotdgTrainingMasterSchema = z.object({
  master_id: lotdgIdentifierSchema,
  master_name: z.string().min(1),
  master_level: z.int().min(1),
  weapon_name: z.string(),
  victory_message: z.string(),
  defeat_message: z.string(),
  health: z.int().min(1),
  attack_point: lotdgNonNegativeIntegerSchema,
  defense_point: lotdgNonNegativeIntegerSchema,
})

export const lotdgMountSchema = z.object({
  mount_id: lotdgIdentifierSchema,
  mount_name: z.string().min(1),
  mount_description: z.string(),
  mount_category: z.string(),
  buff_json: lotdgJsonObjectSchema,
  cost_gem: lotdgNonNegativeIntegerSchema,
  cost_gold: lotdgNonNegativeIntegerSchema,
  is_active: lotdgSqliteBooleanSchema,
  extra_forest_fight: z.int(),
  tavern_access_level: lotdgNonNegativeIntegerSchema,
  new_day_message: z.string(),
  recharge_message: z.string(),
  partial_recharge_message: z.string(),
  mine_can_enter: lotdgSqliteBooleanSchema,
  mine_can_die: lotdgSqliteBooleanSchema,
  mine_can_save: lotdgSqliteBooleanSchema,
  mine_tether_message: z.string(),
  mine_death_message: z.string(),
  mine_save_message: z.string(),
})

export const lotdgRiddleSchema = z.object({
  riddle_id: lotdgIdentifierSchema,
  riddle_text: z.string().min(1),
  answer_text: z.string().min(1),
})

export const lotdgTauntSchema = z.object({
  taunt_id: lotdgIdentifierSchema,
  taunt_text: z.string().min(1),
  editor_name: z.string(),
})

export type LotdgWeapon = z.infer<typeof lotdgWeaponSchema>
export type LotdgArmor = z.infer<typeof lotdgArmorSchema>
export type LotdgCreature = z.infer<typeof lotdgCreatureSchema>
export type LotdgTrainingMaster = z.infer<typeof lotdgTrainingMasterSchema>
export type LotdgMount = z.infer<typeof lotdgMountSchema>
export type LotdgRiddle = z.infer<typeof lotdgRiddleSchema>
export type LotdgTaunt = z.infer<typeof lotdgTauntSchema>
