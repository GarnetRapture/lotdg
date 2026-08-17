import { z } from 'zod'

export const lotdgSqliteBooleanSchema = z.union([z.literal(0), z.literal(1)])

export const lotdgSqliteDateTimeSchema = z
  .string()
  .regex(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/, 'SQLite datetime 형식이 아닙니다')

export const lotdgSqliteDateSchema = z
  .string()
  .regex(/^\d{4}-\d{2}-\d{2}$/, 'SQLite date 형식이 아닙니다')

export const lotdgJsonObjectSchema = z.record(z.string(), z.unknown())

export const lotdgJsonArraySchema = z.array(z.unknown())

export const lotdgIdentifierSchema = z.int().positive()

export const lotdgNonNegativeIntegerSchema = z.int().min(0)
