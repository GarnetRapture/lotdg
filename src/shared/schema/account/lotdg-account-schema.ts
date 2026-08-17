import { z } from 'zod'
import { LOTDG_SUPPORTED_LOCALE_CODE_LIST } from '../../constant/lotdg-supported-locale'
import {
  lotdgIdentifierSchema,
  lotdgJsonObjectSchema,
  lotdgNonNegativeIntegerSchema,
  lotdgSqliteBooleanSchema,
  lotdgSqliteDateTimeSchema,
} from '../system/lotdg-sqlite-primitive-schema'

export const lotdgLocaleCodeSchema = z.enum(
  LOTDG_SUPPORTED_LOCALE_CODE_LIST as unknown as [string, ...string[]],
)

export const lotdgAccountSchema = z.object({
  account_id: lotdgIdentifierSchema,
  login_name: z.string().min(1).max(50),
  email_address: z.string().default(''),
  email_validated: lotdgSqliteBooleanSchema,
  is_locked: lotdgSqliteBooleanSchema,
  is_logged_in: lotdgSqliteBooleanSchema,
  created_at: lotdgSqliteDateTimeSchema,
  last_seen_at: lotdgSqliteDateTimeSchema.nullable(),
  last_hit_at: lotdgSqliteDateTimeSchema.nullable(),
})

export const lotdgAccountPrivilegeSchema = z.object({
  account_id: lotdgIdentifierSchema,
  superuser_level: z.int().min(0).max(3),
  superuser_flag_bitmap: lotdgNonNegativeIntegerSchema,
  ban_override: lotdgSqliteBooleanSchema,
  beta_enabled: lotdgSqliteBooleanSchema,
})

export const lotdgAccountPreferenceSchema = z.object({
  account_id: lotdgIdentifierSchema,
  locale_code: lotdgLocaleCodeSchema,
  template_name: z.string().min(1),
  preference_json: lotdgJsonObjectSchema,
})

export const lotdgAccountDonationSchema = z.object({
  account_id: lotdgIdentifierSchema,
  donation_point: lotdgNonNegativeIntegerSchema,
  donation_point_spent: lotdgNonNegativeIntegerSchema,
  donation_config_json: lotdgJsonObjectSchema,
})

export const lotdgAccountReferralSchema = z.object({
  account_id: lotdgIdentifierSchema,
  referrer_account_id: lotdgIdentifierSchema.nullable(),
  referral_awarded: lotdgSqliteBooleanSchema,
})

export const lotdgAccountBundleSchema = z.object({
  account: lotdgAccountSchema,
  privilege: lotdgAccountPrivilegeSchema,
  preference: lotdgAccountPreferenceSchema,
})

export type LotdgAccount = z.infer<typeof lotdgAccountSchema>
export type LotdgAccountPrivilege = z.infer<typeof lotdgAccountPrivilegeSchema>
export type LotdgAccountPreference = z.infer<typeof lotdgAccountPreferenceSchema>
export type LotdgAccountDonation = z.infer<typeof lotdgAccountDonationSchema>
export type LotdgAccountReferral = z.infer<typeof lotdgAccountReferralSchema>
export type LotdgAccountBundle = z.infer<typeof lotdgAccountBundleSchema>
