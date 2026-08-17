import { z } from 'zod'
import { lotdgIdentifierSchema } from '../system/lotdg-sqlite-primitive-schema'
import { lotdgCommentaryEntrySchema } from './lotdg-commentary-schema'

export const lotdgBountyBoardSchema = z.object({
  own_bounty: z.int(),
  bounty_set_today: z.int(),
  maximum_bounty_per_day: z.int(),
  listing_fee_percent: z.int(),
  minimum_per_level: z.int(),
  maximum_per_level: z.int(),
  minimum_target_level: z.int(),
  bounty_list: z.array(
    z.object({
      character_id: lotdgIdentifierSchema,
      display_name: z.string(),
      level: z.int(),
      sex_code: z.int(),
      location_code: z.int(),
      is_alive: z.boolean(),
      bounty: z.int(),
      is_logged_in: z.boolean(),
      last_seen_at: z.string().nullable(),
    }),
  ),
})

export const lotdgBountySearchSchema = z.object({
  search_term: z.string(),
  candidate_list: z.array(
    z.object({
      character_id: lotdgIdentifierSchema,
      display_name: z.string(),
      level: z.int(),
      current_bounty: z.int(),
      minimum_bounty: z.int(),
      remaining_bounty: z.int(),
      eligible: z.boolean(),
    }),
  ),
})

export const lotdgBountyPlacementSchema = z.object({
  placed: z.boolean(),
  message_key: z.string().optional(),
  target_display_name: z.string().optional(),
  amount: z.int().optional(),
  listing_fee: z.int().optional(),
  total_cost: z.int().optional(),
  minimum: z.int().optional(),
  maximum: z.int().optional(),
  current_bounty: z.int().optional(),
})

export const lotdgGypsyInspectSchema = z.object({
  gold: z.int(),
  cost: z.int(),
  affordable: z.boolean(),
})

export const lotdgGypsyListenSchema = z.object({
  listened: z.boolean(),
  message_key: z.string().optional(),
  cost: z.int().optional(),
  section_code: z.string().optional(),
  comment_list: z.array(lotdgCommentaryEntrySchema).optional(),
  post_quota_remaining: z.int().optional(),
})

const rankedEntrySchema = z.object({
  rank: z.int(),
  display_name: z.string(),
  level: z.int().optional(),
  experience: z.int().optional(),
  gold: z.int().optional(),
  gold_in_bank: z.int().optional(),
  attack_point: z.int().optional(),
  defence_point: z.int().optional(),
  dragon_kill_count: z.int().optional(),
  player_kill_count: z.int().optional(),
  resurrection_count: z.int().optional(),
  generation_count: z.int().optional(),
})

export const lotdgHallOfFameSchema = z.object({
  dragon_slayer: z.array(rankedEntrySchema),
  top_warrior: z.array(rankedEntrySchema),
  wealthiest: z.array(rankedEntrySchema),
  strongest: z.array(rankedEntrySchema),
  bounty_hunter: z.array(rankedEntrySchema),
  most_resurrected: z.array(rankedEntrySchema),
  most_active: z.array(rankedEntrySchema),
})

export const lotdgWarriorListSchema = z.object({
  mode: z.string(),
  total_player_count: z.int(),
  page: z.int().optional(),
  page_count: z.int().optional(),
  range_from: z.int().optional(),
  range_to: z.int().optional(),
  search_term: z.string().optional(),
  truncated: z.boolean().optional(),
  warrior_list: z.array(
    z.object({
      character_id: lotdgIdentifierSchema,
      login_name: z.string(),
      display_name: z.string(),
      level: z.int(),
      sex_code: z.int(),
      is_alive: z.boolean(),
      location_code: z.int(),
      is_online: z.boolean(),
      days_since_last_seen: z.int().nullable(),
    }),
  ),
})

export const lotdgBiographySchema = z.object({
  character_id: lotdgIdentifierSchema,
  login_name: z.string(),
  display_name: z.string(),
  rank_title: z.string(),
  level: z.int(),
  sex_code: z.int(),
  race_code: z.int(),
  specialty_code: z.int(),
  resurrection_count: z.int(),
  dragon_kill_count: z.int(),
  mount_name: z.string().nullable(),
  biography: z.string(),
  news_history: z.array(
    z.object({ news_id: z.int(), news_text: z.string(), news_date: z.string() }),
  ),
})

export const lotdgPreferenceInspectSchema = z.object({
  locale_code: z.string(),
  template_name: z.string(),
  email_address: z.string(),
  email_change_allowed: z.boolean(),
  biography: z.string(),
  biography_editable: z.boolean(),
  notification: z.record(z.string(), z.union([z.int(), z.string()])),
  self_delete_allowed: z.boolean(),
})

export const lotdgPreferenceMutationSchema = z.object({
  saved: z.boolean().optional(),
  changed: z.boolean().optional(),
  deleted: z.boolean().optional(),
  message_key: z.string().optional(),
  notice_key_list: z.array(z.string()).optional(),
})

export const lotdgSocialVenueEnterSchema = z.object({
  entered: z.boolean(),
  message_key: z.string().optional(),
  venue_code: z.string().optional(),
  comment_list: z.array(lotdgCommentaryEntrySchema).optional(),
  post_quota_remaining: z.int().optional(),
})

const pollResultSchema = z.object({
  count_by_choice: z.record(z.string(), z.int()),
  total_vote: z.int(),
  own_choice: z.int().nullable(),
})

export const lotdgMessageOfTheDayListSchema = z.object({
  notice_list: z.array(
    z.object({
      motd_id: lotdgIdentifierSchema,
      title: z.string(),
      body: z.string(),
      motd_type: z.int(),
      posted_at: z.string(),
      is_unseen: z.boolean(),
      choice_list: z.array(z.string()),
      poll_result: pollResultSchema.nullable(),
    }),
  ),
  has_unseen: z.boolean(),
})

export const lotdgMessageOfTheDayVoteSchema = z.object({
  voted: z.boolean(),
  message_key: z.string().optional(),
  poll_result: pollResultSchema.optional(),
})

export const lotdgMessageOfTheDaySeenSchema = z.object({
  marked_seen: z.boolean(),
})

export type LotdgBountyBoard = z.infer<typeof lotdgBountyBoardSchema>
export type LotdgBountySearch = z.infer<typeof lotdgBountySearchSchema>
export type LotdgGypsyInspect = z.infer<typeof lotdgGypsyInspectSchema>
export type LotdgHallOfFame = z.infer<typeof lotdgHallOfFameSchema>
export type LotdgWarriorList = z.infer<typeof lotdgWarriorListSchema>
export type LotdgBiography = z.infer<typeof lotdgBiographySchema>
export type LotdgPreferenceInspect = z.infer<typeof lotdgPreferenceInspectSchema>
export type LotdgSocialVenueEnter = z.infer<typeof lotdgSocialVenueEnterSchema>
export type LotdgMessageOfTheDayList = z.infer<typeof lotdgMessageOfTheDayListSchema>
export type LotdgRankedEntry = z.infer<typeof rankedEntrySchema>
