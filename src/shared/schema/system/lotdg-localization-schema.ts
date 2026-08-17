import { z } from 'zod'
import {
  LOTDG_LOCALE_NAMESPACE_LIST,
  LOTDG_SUPPORTED_LOCALE_CODE_LIST,
} from '../../constant/lotdg-supported-locale'

export const lotdgLocalizationLocaleCodeSchema = z.enum(
  LOTDG_SUPPORTED_LOCALE_CODE_LIST as unknown as [string, ...string[]],
)

export const lotdgLocalizationNamespaceSchema = z.enum(
  LOTDG_LOCALE_NAMESPACE_LIST as unknown as [string, ...string[]],
)

export const lotdgLocaleResourceSchema = z.record(
  z.string().regex(/^[a-z0-9]+(?:[-.][a-z0-9]+)*$/, '라벨 키는 소문자·숫자·하이픈·점만 허용합니다'),
  z.string(),
)

export const lotdgLabelKeySchema = z.object({
  label_key_id: z.int().positive(),
  namespace_code: lotdgLocalizationNamespaceSchema,
  label_path: z.string().min(1),
  source_reference: z.string(),
  placeholder_json: z.array(z.string()),
})

export const lotdgLabelTranslationSchema = z.object({
  label_key_id: z.int().positive(),
  locale_code: lotdgLocalizationLocaleCodeSchema,
  translation_text: z.string(),
})

export const lotdgCatalogTranslationSchema = z.object({
  entity_type: z.enum([
    'weapon',
    'armor',
    'creature',
    'training_master',
    'mount',
    'riddle',
    'taunt',
  ]),
  entity_id: z.int().positive(),
  field_code: z.string().min(1),
  locale_code: lotdgLocalizationLocaleCodeSchema,
  translation_text: z.string(),
})

export const lotdgLocaleBundleSchema = z.object({
  locale_code: lotdgLocalizationLocaleCodeSchema,
  namespace: z.record(lotdgLocalizationNamespaceSchema, lotdgLocaleResourceSchema),
})

export type LotdgLocaleResource = z.infer<typeof lotdgLocaleResourceSchema>
export type LotdgLabelKey = z.infer<typeof lotdgLabelKeySchema>
export type LotdgLabelTranslation = z.infer<typeof lotdgLabelTranslationSchema>
export type LotdgCatalogTranslation = z.infer<typeof lotdgCatalogTranslationSchema>
export type LotdgLocaleBundle = z.infer<typeof lotdgLocaleBundleSchema>
