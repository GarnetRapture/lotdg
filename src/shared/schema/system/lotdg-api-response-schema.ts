import { z } from 'zod'
import { lotdgIdentifierSchema } from './lotdg-sqlite-primitive-schema'

export const lotdgLoginResponseSchema = z.object({
  authenticated: z.boolean(),
  account_id: lotdgIdentifierSchema.optional(),
  character_id: lotdgIdentifierSchema.nullable().optional(),
  message_key: z.string().optional(),
  privilege: z
    .object({
      account_id: lotdgIdentifierSchema,
      superuser_level: z.int(),
      superuser_flag_bitmap: z.int(),
      ban_override: z.int(),
      beta_enabled: z.int(),
    })
    .nullable()
    .optional(),
  preference: z
    .object({
      account_id: lotdgIdentifierSchema,
      locale_code: z.string(),
      template_name: z.string(),
      preference_json: z.string(),
    })
    .nullable()
    .optional(),
})

export const lotdgRegisterResponseSchema = z.object({
  registered: z.boolean(),
  account_id: lotdgIdentifierSchema.optional(),
  character_id: lotdgIdentifierSchema.optional(),
  login_name: z.string().optional(),
  email_validation_required: z.boolean().optional(),
  message_key_list: z.array(z.string()).optional(),
})

export const lotdgVillageResponseSchema = z.object({
  entered: z.boolean(),
  redirect: z.string().optional(),
  message_key: z.string().optional(),
  display_name: z.string().optional(),
  level: z.int().optional(),
  game_time: z.string().optional(),
  real_seconds_until_new_day: z.int().optional(),
  latest_news: z
    .object({ news_id: z.int(), news_text: z.string(), news_date: z.string() })
    .nullable()
    .optional(),
  auto_master_challenge: z
    .object({
      triggered: z.boolean(),
      reason_key: z.string().optional(),
      next_level_requirement: z.int().optional(),
    })
    .optional(),
  pvp_enabled: z.boolean().optional(),
  destination_list: z
    .array(z.object({ group_key: z.string(), code: z.string(), label_key: z.string() }))
    .optional(),
})

export const lotdgCharacterPanelSchema = z.object({
  character: z.object({
    character_id: lotdgIdentifierSchema,
    display_name: z.string(),
    level: z.int(),
    rank_title: z.string(),
    location_code: z.int(),
  }),
  vital: z.object({
    hit_point: z.int(),
    max_hit_point: z.int(),
    is_alive: z.int(),
    spirit_level: z.int(),
    soul_point: z.int(),
    grave_fight: z.int(),
  }),
  combat_stat: z.object({ attack_point: z.int(), defence_point: z.int() }),
  progression: z.object({ experience: z.int(), dragon_kill_count: z.int() }),
  equipment: z.object({ weapon_name: z.string(), armor_name: z.string() }),
  wealth: z.object({ gold: z.int(), gold_in_bank: z.int(), gem: z.int() }),
  daily_allowance: z.object({ forest_turn: z.int(), player_fight: z.int() }),
})

const enemySchema = z.object({
  creature_name: z.string(),
  creature_level: z.int(),
  weapon_name: z.string(),
  health: z.int(),
  max_health: z.int(),
})

export const lotdgForestEncounterSchema = z.object({
  encountered: z.boolean(),
  message_key: z.string().optional(),
  enemy_first_strike: z.boolean().optional(),
  enemy: enemySchema.optional(),
})

export const lotdgBattleRoundSchema = z.object({
  fought: z.boolean(),
  message_key: z.string().optional(),
  damage_to_enemy: z.int().optional(),
  damage_to_player: z.int().optional(),
  critical_attack: z.boolean().optional(),
  player_hit_point: z.int().optional(),
  enemy_hit_point: z.int().optional(),
  victory: z.boolean().optional(),
  defeat: z.boolean().optional(),
  reward: z.object({ gold: z.int(), experience: z.int() }).nullable().optional(),
})

export const lotdgTrainingInspectSchema = z.object({
  has_master: z.boolean(),
  level: z.int(),
  master_name: z.string().optional(),
  master_weapon_name: z.string().optional(),
  required_experience: z.int().optional(),
  current_experience: z.int().optional(),
  missing_experience: z.int().optional(),
  can_challenge: z.boolean().optional(),
  already_challenged_today: z.boolean().optional(),
})

export const lotdgTrainingChallengeSchema = z.object({
  challenged: z.boolean(),
  message_key: z.string().optional(),
  victory: z.boolean().optional(),
  master_name: z.string().optional(),
  master_message: z.string().optional(),
  required_experience: z.int().optional(),
  missing_experience: z.int().optional(),
  advancement: z
    .object({
      level: z.int(),
      max_hit_point: z.int(),
      attack_gain: z.int(),
      defence_gain: z.int(),
      soul_point_gain: z.int(),
    })
    .optional(),
})

export const lotdgBankInspectSchema = z.object({
  gold: z.int(),
  gold_in_bank: z.int(),
  deposit_limit: z.int(),
  borrow_limit: z.int(),
  transfer_out_limit: z.int(),
  transferred_today: z.int(),
  transfer_allowed: z.boolean(),
})

export const lotdgBankOperationSchema = z.object({
  succeeded: z.boolean(),
  message_key: z.string().optional(),
  deposited: z.int().optional(),
  withdrawn: z.int().optional(),
  gold: z.int().optional(),
  gold_in_bank: z.int().optional(),
  net_amount: z.int().optional(),
  fee: z.int().optional(),
  recipient_display_name: z.string().optional(),
})

export const lotdgShopBrowseSchema = z.object({
  shop_type: z.string(),
  dragon_kill_tier: z.int(),
  trade_in_value: z.int(),
  gold: z.int(),
  item_list: z.array(
    z.object({
      item_id: lotdgIdentifierSchema,
      item_name: z.string(),
      price: z.int(),
      power: z.int(),
      affordable: z.boolean(),
    }),
  ),
})

export const lotdgShopBuySchema = z.object({
  succeeded: z.boolean(),
  message_key: z.string().optional(),
  item_name: z.string().optional(),
  price: z.int().optional(),
  trade_in_value: z.int().optional(),
  power: z.int().optional(),
  slain: z.boolean().optional(),
  gold_lost: z.int().optional(),
})

export const lotdgInnEnterSchema = z.object({
  display_name: z.string(),
  gold: z.int(),
  gold_in_bank: z.int(),
  drunkenness: z.int(),
  ale_price: z.int(),
  room_price: z.int(),
  room_price_from_bank: z.int(),
  bought_room_today: z.boolean(),
  can_drink: z.boolean(),
})

export const lotdgInnActionSchema = z.object({
  bought: z.boolean().optional(),
  rented: z.boolean().optional(),
  changed: z.boolean().optional(),
  message_key: z.string().optional(),
  ale_price: z.int().optional(),
  drunkenness: z.int().optional(),
  healed_hit_point: z.int().optional(),
  gained_turn: z.int().optional(),
  price: z.int().optional(),
  paid_from_bank: z.boolean().optional(),
  already_paid: z.boolean().optional(),
  specialty_code: z.int().optional(),
})

export const lotdgGraveyardInspectSchema = z.object({
  is_alive: z.boolean(),
  soul_point: z.int(),
  maximum_soul_point: z.int(),
  grave_fight: z.int(),
  death_power: z.int(),
  restore_favor_cost: z.int(),
  can_resurrect: z.boolean(),
  can_haunt: z.boolean(),
})

export const lotdgGraveyardActionSchema = z.object({
  encountered: z.boolean().optional(),
  fought: z.boolean().optional(),
  restored: z.boolean().optional(),
  haunted: z.boolean().optional(),
  message_key: z.string().optional(),
  enemy: enemySchema.optional(),
  victory: z.boolean().optional(),
  defeat: z.boolean().optional(),
  favor_gained: z.int().optional(),
  favor_spent: z.int().optional(),
  soul_point: z.int().optional(),
  enemy_hit_point: z.int().optional(),
  damage_to_enemy: z.int().optional(),
  damage_to_spirit: z.int().optional(),
  enemy_name: z.string().optional(),
  target_display_name: z.string().optional(),
})

export const lotdgDragonEnterSchema = z.object({
  entered: z.boolean(),
  message_key: z.string().optional(),
  required_level: z.int().optional(),
  dragon: z
    .object({
      creature_name: z.string(),
      creature_level: z.int(),
      weapon_name: z.string(),
      health: z.int(),
      max_health: z.int(),
      attack_point: z.int(),
      defense_point: z.int(),
    })
    .optional(),
})

export const lotdgDragonRoundSchema = z.object({
  fought: z.boolean(),
  message_key: z.string().optional(),
  victory: z.boolean().optional(),
  defeat: z.boolean().optional(),
  flawless: z.boolean().optional(),
  damage_to_dragon: z.int().optional(),
  damage_to_player: z.int().optional(),
  player_hit_point: z.int().optional(),
  dragon_hit_point: z.int().optional(),
})

export const lotdgDragonRebirthSchema = z.object({
  rebirth: z.boolean(),
  dragon_kill_count: z.int(),
  new_title: z.string(),
  display_name: z.string(),
  gold: z.int(),
  gem_gain: z.int(),
  retained_max_hit_point: z.int(),
  charm_gain: z.int(),
  flawless: z.boolean(),
})

export const lotdgPvpListSchema = z.object({
  player_fight: z.int(),
  target_list: z.array(
    z.object({
      character_id: lotdgIdentifierSchema,
      display_name: z.string(),
      level: z.int(),
      player_kill_count: z.int(),
      attackable: z.boolean(),
    }),
  ),
})

export const lotdgPvpAttackSchema = z.object({
  attacked: z.boolean(),
  message_key: z.string().optional(),
  victory: z.boolean().optional(),
  defender_display_name: z.string().optional(),
  gold_looted: z.int().optional(),
  bounty_collected: z.int().optional(),
  experience_gained: z.int().optional(),
  gold_lost: z.int().optional(),
})

export const lotdgNewsListSchema = z.object({
  news_date: z.string(),
  day_offset: z.int(),
  page: z.int(),
  page_count: z.int(),
  range_from: z.int(),
  range_to: z.int(),
  total_count: z.int(),
  news_list: z.array(
    z.object({
      news_id: z.int(),
      news_text: z.string(),
      character_id: z.int().nullable(),
      display_name: z.string(),
    }),
  ),
})

export const lotdgNewsRemovalSchema = z.object({
  removed: z.boolean(),
})

export const lotdgMailInboxSchema = z.object({
  message_list: z.array(
    z.object({
      mail_message_id: lotdgIdentifierSchema,
      subject: z.string(),
      sender_display_name: z.string(),
      is_system_message: z.boolean(),
      is_seen: z.boolean(),
      sent_at: z.string(),
    }),
  ),
  unseen_count: z.int(),
  seen_count: z.int(),
  inbox_limit: z.int(),
})

export const lotdgMailReadSchema = z.object({
  found: z.boolean(),
  message_key: z.string().optional(),
  subject: z.string().optional(),
  body: z.string().optional(),
  sender_display_name: z.string().optional(),
  sent_at: z.string().optional(),
})

export const lotdgMailMutationSchema = z.object({
  sent: z.boolean().optional(),
  deleted: z.boolean().optional(),
  deleted_count: z.int().optional(),
  message_key: z.string().optional(),
  size_limit: z.int().optional(),
  recipient_account_id: z.int().optional(),
})

export const lotdgMailReplySchema = z.object({
  prepared: z.boolean(),
  message_key: z.string().optional(),
  recipient_login_name: z.string().optional(),
  recipient_display_name: z.string().optional(),
  subject: z.string().optional(),
  quoted_body: z.string().optional(),
})

export const lotdgMailRecipientSearchSchema = z.object({
  search_term: z.string(),
  candidate_list: z.array(
    z.object({ login_name: z.string(), display_name: z.string() }),
  ),
})

export const lotdgNewDaySchema = z.object({
  new_day: z.boolean(),
  resurrection: z.boolean(),
  game_age_day: z.int(),
  spirit_level: z.int(),
  forest_turn: z.int(),
  turn_note_key_list: z.array(z.string()),
  interest_rate_percent: z.int(),
  interest_gold: z.int(),
  hit_point: z.int(),
  player_fight: z.int(),
})

export const lotdgAdministrationSummarySchema = z.object({
  account_count: z.int(),
  character_count: z.int(),
  creature_count: z.int(),
  weapon_count: z.int(),
  armor_count: z.int(),
  mail_count: z.int(),
  news_count: z.int(),
  petition_count: z.int(),
  ban_count: z.int(),
})

export const lotdgAdministrationAccountListSchema = z.object({
  account_list: z.array(
    z.object({
      account_id: lotdgIdentifierSchema,
      login_name: z.string(),
      is_locked: z.boolean(),
      is_logged_in: z.boolean(),
      superuser_level: z.int(),
      character_id: z.int().nullable(),
      display_name: z.string(),
      level: z.int().nullable(),
      dragon_kill_count: z.int().nullable(),
      last_seen_at: z.string().nullable(),
    }),
  ),
})

export const lotdgAdministrationSettingListSchema = z.object({
  setting_map: z.record(z.string(), z.string()),
})

export const lotdgAdministrationMutationSchema = z.object({
  saved: z.boolean().optional(),
  updated: z.boolean().optional(),
  created: z.boolean().optional(),
  removed: z.boolean().optional(),
  message_key: z.string().optional(),
  setting_key: z.string().optional(),
  setting_value: z.string().optional(),
  is_locked: z.boolean().optional(),
  superuser_level: z.int().optional(),
  access_ban_id: z.int().optional(),
})

export const lotdgPetitionListSchema = z.object({
  petition_list: z.array(
    z.object({
      petition_id: lotdgIdentifierSchema,
      status_code: z.int(),
      body: z.string(),
      display_name: z.string(),
      submitted_at: z.string(),
    }),
  ),
  status_summary: z.object({ unseen: z.int(), seen: z.int(), closed: z.int() }),
})

export const lotdgPetitionMutationSchema = z.object({
  submitted: z.boolean().optional(),
  updated: z.boolean().optional(),
  petition_id: z.int().optional(),
  status_code: z.int().optional(),
  message_key: z.string().optional(),
})

export type LotdgAdministrationSummary = z.infer<typeof lotdgAdministrationSummarySchema>
export type LotdgAdministrationAccountList = z.infer<typeof lotdgAdministrationAccountListSchema>
export type LotdgPetitionList = z.infer<typeof lotdgPetitionListSchema>
export type LotdgCharacterPanel = z.infer<typeof lotdgCharacterPanelSchema>
export type LotdgVillageResponse = z.infer<typeof lotdgVillageResponseSchema>
export type LotdgForestEncounter = z.infer<typeof lotdgForestEncounterSchema>
export type LotdgBattleRound = z.infer<typeof lotdgBattleRoundSchema>
export type LotdgShopBrowse = z.infer<typeof lotdgShopBrowseSchema>
export type LotdgBankInspect = z.infer<typeof lotdgBankInspectSchema>
export type LotdgInnEnter = z.infer<typeof lotdgInnEnterSchema>
export type LotdgGraveyardInspect = z.infer<typeof lotdgGraveyardInspectSchema>
export type LotdgPvpList = z.infer<typeof lotdgPvpListSchema>
export type LotdgNewsList = z.infer<typeof lotdgNewsListSchema>
export type LotdgMailInbox = z.infer<typeof lotdgMailInboxSchema>
