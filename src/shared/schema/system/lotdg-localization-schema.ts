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

/**
 * 라벨 키 문법. 마디는 점으로 잇고, 마디 안에는 레거시 식별자가 그대로 들어온다 —
 * game_setting 의 LOGINTIMEOUT 처럼 대문자가, hall_of_fame 구획 코드처럼 밑줄이
 * 섞인다. 한 키라도 어긋나면 z.record 가 파일 전체를 버려 네임스페이스가 통째로
 * 비므로, 실제로 쓰이는 문자 집합을 모두 허용한다.
 */
export const lotdgLocaleResourceSchema = z.record(
  z
    .string()
    .regex(
      /^[A-Za-z0-9_]+(?:[-.][A-Za-z0-9_]+)*$/,
      '라벨 키는 영숫자·밑줄·하이픈·점만 허용합니다',
    ),
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
