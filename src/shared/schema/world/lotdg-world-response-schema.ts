import { z } from 'zod'
import { lotdgIdentifierSchema } from '../system/lotdg-sqlite-primitive-schema'

export const lotdgHealerInspectSchema = z.object({
  is_golinda: z.boolean(),
  hit_point: z.int(),
  max_hit_point: z.int(),
  gold: z.int(),
  full_heal_cost: z.int(),
  needs_healing: z.boolean(),
  price_list: z.array(z.object({ percent: z.int(), price: z.int() })),
})

export const lotdgHealerPurchaseSchema = z.object({
  healed: z.boolean(),
  message_key: z.string().optional(),
  percent: z.int().optional(),
  price: z.int().optional(),
  healed_hit_point: z.int().optional(),
})

export const lotdgStableBrowseSchema = z.object({
  gold: z.int(),
  gem: z.int(),
  current_mount: z
    .object({
      mount_id: lotdgIdentifierSchema,
      mount_name: z.string(),
      resale_gold: z.int(),
      resale_gem: z.int(),
    })
    .nullable(),
  mount_list: z.array(
    z.object({
      mount_id: lotdgIdentifierSchema,
      mount_name: z.string(),
      mount_description: z.string(),
      mount_category: z.string(),
      cost_gold: z.int(),
      cost_gem: z.int(),
      extra_forest_fight: z.int(),
      tavern_access_level: z.int(),
    }),
  ),
})

export const lotdgStableMutationSchema = z.object({
  bought: z.boolean().optional(),
  sold: z.boolean().optional(),
  message_key: z.string().optional(),
  mount_name: z.string().optional(),
  cost_gold: z.int().optional(),
  cost_gem: z.int().optional(),
  trade_in_gold: z.int().optional(),
  trade_in_gem: z.int().optional(),
  resale_gold: z.int().optional(),
  resale_gem: z.int().optional(),
})

export const lotdgGemTraderInspectSchema = z.object({
  available: z.boolean(),
  gold: z.int(),
  gem: z.int(),
  stock: z.int(),
  sell_price_per_gem: z.int(),
  purchase_option_list: z.array(
    z.object({
      option_code: z.int(),
      gem: z.int(),
      gold: z.int(),
      available: z.boolean(),
    }),
  ),
})

export const lotdgGemTraderMutationSchema = z.object({
  bought: z.boolean().optional(),
  sold: z.boolean().optional(),
  message_key: z.string().optional(),
  gem: z.int().optional(),
  gold: z.int().optional(),
  stock: z.int().optional(),
})

export const lotdgOuthouseInspectSchema = z.object({
  used_today: z.boolean(),
  gold: z.int(),
  private_cost: z.int(),
  can_pay: z.boolean(),
})

export const lotdgOuthouseActionSchema = z.object({
  used: z.boolean().optional(),
  washed: z.boolean().optional(),
  skipped: z.boolean().optional(),
  rewarded: z.boolean().optional(),
  punished: z.boolean().optional(),
  message_key: z.string().optional(),
  toilet_type: z.string().optional(),
  paid: z.int().optional(),
  gold_gained: z.int().optional(),
  gem_gained: z.int().optional(),
  gold_lost: z.int().optional(),
  drunkenness: z.int().optional(),
})

export type LotdgHealerInspect = z.infer<typeof lotdgHealerInspectSchema>
export type LotdgStableBrowse = z.infer<typeof lotdgStableBrowseSchema>
export type LotdgGemTraderInspect = z.infer<typeof lotdgGemTraderInspectSchema>
export type LotdgOuthouseInspect = z.infer<typeof lotdgOuthouseInspectSchema>
