import { z } from 'zod'
import { lotdgIdentifierSchema } from '../system/lotdg-sqlite-primitive-schema'

export const lotdgCommentaryEntrySchema = z.object({
  commentary_id: lotdgIdentifierSchema,
  character_id: z.int().nullable(),
  display_name: z.string(),
  comment_text: z.string(),
  posted_at: z.string(),
})

export const lotdgCommentaryListSchema = z.object({
  section_code: z.string(),
  page: z.int(),
  comment_list: z.array(lotdgCommentaryEntrySchema),
  post_quota_remaining: z.int(),
})

export const lotdgCommentaryPostSchema = z.object({
  posted: z.boolean(),
  message_key: z.string().optional(),
  comment_text: z.string().optional(),
  post_quota_remaining: z.int().optional(),
})

export type LotdgCommentaryEntry = z.infer<typeof lotdgCommentaryEntrySchema>
export type LotdgCommentaryList = z.infer<typeof lotdgCommentaryListSchema>
