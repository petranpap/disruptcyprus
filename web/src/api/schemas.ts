import { z } from 'zod'

/** Response shapes, mirroring the Laravel API Resources (see docs/API.md). */

export const localeSchema = z.enum(['el', 'en'])
export type Locale = z.infer<typeof localeSchema>

export const userSchema = z.object({
  id: z.number(),
  name: z.string(),
  email: z.string(),
  email_verified: z.boolean(),
  avatar_url: z.string().nullable(),
  role: z.enum(['reader', 'editor', 'admin']),
  locale: localeSchema,
  content_locales: z.array(localeSchema),
  timezone: z.string(),
  has_password: z.boolean(),
  needs_consent: z.boolean(),
  onboarded: z.boolean(),
  created_at: z.string().nullable(),
})
export type User = z.infer<typeof userSchema>

export const industryGroupSchema = z.enum([
  'finance_investment',
  'deep_tech',
  'digital_software',
  'sectors',
  'society_gov',
])
export type IndustryGroup = z.infer<typeof industryGroupSchema>

export const industrySchema = z.object({
  id: z.number(),
  slug: z.string(),
  name: z.string(),
  group: industryGroupSchema,
  color: z.string(),
  image_url: z.string().nullable(),
  sort_order: z.number(),
})
export type Industry = z.infer<typeof industrySchema>

export const sectionSchema = z.object({
  id: z.number(),
  slug: z.string(),
  name: z.string(),
  has_articles: z.boolean(),
  sort_order: z.number(),
})
export type Section = z.infer<typeof sectionSchema>

export const myIndustriesSchema = z.object({
  industry_ids: z.array(z.number()),
  notify_ids: z.array(z.number()),
})
export type MyIndustries = z.infer<typeof myIndustriesSchema>

export const notificationPreferencesSchema = z.object({
  digest_news_daily: z.boolean(),
  digest_news_monthly: z.boolean(),
  digest_events_weekly: z.boolean(),
  digest_events_monthly: z.boolean(),
  event_reminders: z.boolean(),
  delivery_time: z.string(),
})
export type NotificationPreferences = z.infer<typeof notificationPreferencesSchema>

const industryBadgeSchema = z.object({ slug: z.string(), name: z.string(), color: z.string() })
const imageSchema = z.object({ thumb: z.string(), card: z.string(), hero: z.string() }).nullable()

export const articleCardSchema = z.object({
  type: z.literal('article'),
  id: z.number(),
  slug: z.string(),
  title: z.string().nullable(),
  excerpt: z.string().nullable(),
  section: z.object({ slug: z.string(), name: z.string() }),
  primary_industry: industryBadgeSchema.nullable(),
  industries: z.array(industryBadgeSchema.extend({ is_primary: z.boolean() })),
  author: z.object({ id: z.number(), name: z.string(), is_verified: z.boolean() }),
  is_original: z.boolean(),
  is_featured: z.boolean(),
  published_at: z.string().nullable(),
  reading_time_minutes: z.number().nullable(),
  reads: z.number(),
  image: imageSchema,
  locale: localeSchema,
  is_fallback: z.boolean(),
  is_bookmarked: z.boolean(),
})
export type ArticleCard = z.infer<typeof articleCardSchema>

export const eventCardSchema = z.object({
  type: z.literal('event'),
  id: z.number(),
  slug: z.string(),
  title: z.string().nullable(),
  excerpt: z.string().nullable(),
  starts_at: z.string(),
  ends_at: z.string().nullable(),
  timezone: z.string(),
  is_online: z.boolean(),
  city: z.string().nullable(),
  location_name: z.string().nullable(),
  price_info: z.string().nullable(),
  primary_industry: industryBadgeSchema.nullable(),
  industries: z.array(industryBadgeSchema.extend({ is_primary: z.boolean() })),
  is_featured: z.boolean(),
  image: imageSchema,
  locale: localeSchema,
  is_fallback: z.boolean(),
  is_bookmarked: z.boolean(),
})
export type EventCard = z.infer<typeof eventCardSchema>

export const contentCardSchema = z.discriminatedUnion('type', [articleCardSchema, eventCardSchema])
export type ContentCard = z.infer<typeof contentCardSchema>

export const dataSchema = <T extends z.ZodType>(schema: T) => z.object({ data: schema })

export const cursorPageSchema = <T extends z.ZodType>(item: T) =>
  z.object({
    data: z.array(item),
    meta: z.object({ next_cursor: z.string().nullable() }).loose(),
  })
