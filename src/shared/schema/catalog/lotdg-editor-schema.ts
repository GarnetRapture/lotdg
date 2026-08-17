import { z } from 'zod'
import { lotdgIdentifierSchema } from '../system/lotdg-sqlite-primitive-schema'

export const lotdgEquipmentEditorListSchema = z.object({
  shop_type: z.string(),
  dragon_kill_tier: z.int(),
  maximum_tier: z.int(),
  minimum_power: z.int(),
  maximum_power: z.int(),
  price_by_power: z.record(z.string(), z.int()),
  item_list: z.array(
    z.object({
      item_id: lotdgIdentifierSchema,
      item_name: z.string(),
      power: z.int(),
      price: z.int(),
    }),
  ),
})

export const lotdgEquipmentEditorMutationSchema = z.object({
  saved: z.boolean().optional(),
  removed: z.boolean().optional(),
  message_key: z.string().optional(),
  item_id: z.int().optional(),
  power: z.int().optional(),
  price: z.int().optional(),
})

export const lotdgEquipmentEditorNextPowerSchema = z.object({
  next_power: z.int(),
  price: z.int(),
})

export const lotdgWebVoteSchema = z.object({
  enabled: z.boolean(),
  top_web_id: z.int(),
  last_web_vote_date: z.string().nullable(),
  current_week: z.string(),
  can_claim: z.boolean(),
  gem_reward: z.int(),
})

export const lotdgWebVoteClaimSchema = z.object({
  claimed: z.boolean(),
  message_key: z.string().optional(),
  gem_gained: z.int().optional(),
})

export const lotdgGameInformationSchema = z.object({
  license_code: z.string(),
  original_author: z.string(),
  porter_name: z.string(),
  legacy_version: z.string(),
  days_per_calendar_day: z.int(),
  day_duration_hour: z.int(),
  day_duration_real_minute: z.int(),
  turns_per_day: z.int(),
  server_time: z.string(),
  game_time: z.string(),
  game_date: z.string(),
  real_seconds_until_next_game_day: z.int(),
  setting_group_map: z.record(
    z.string(),
    z.array(z.object({ setting_key: z.string(), setting_value: z.int() })),
  ),
})

export type LotdgEquipmentEditorList = z.infer<typeof lotdgEquipmentEditorListSchema>
export type LotdgWebVote = z.infer<typeof lotdgWebVoteSchema>
export type LotdgGameInformation = z.infer<typeof lotdgGameInformationSchema>
