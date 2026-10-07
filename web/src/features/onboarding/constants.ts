import type { IndustryGroup, NotificationPreferences } from '@/api/schemas'

export const MIN_INDUSTRIES = 3

export const GROUP_ORDER: IndustryGroup[] = [
  'finance_investment',
  'deep_tech',
  'digital_software',
  'sectors',
  'society_gov',
]

export const DELIVERY_TIMES = Array.from({ length: 17 }, (_, index) => `${String(index + 6).padStart(2, '0')}:00`)

/** First visit suggests the two most useful digests; readers can switch them off before finishing. */
export function withSuggestions(preferences: NotificationPreferences): NotificationPreferences {
  const untouched =
    !preferences.digest_news_daily &&
    !preferences.digest_news_monthly &&
    !preferences.digest_events_weekly &&
    !preferences.digest_events_monthly

  return untouched ? { ...preferences, digest_news_daily: true, digest_events_weekly: true } : preferences
}

export type PreferenceToggleKey = Exclude<keyof NotificationPreferences, 'delivery_time'>

/** Digest and reminder switches, shared by onboarding step 3 and Settings → Notifications. */
export const PREFERENCE_TOGGLES: { key: PreferenceToggleKey; label: string; hint: string }[] = [
  { key: 'digest_news_daily', label: 'newsDaily', hint: 'newsDailyHint' },
  { key: 'digest_events_weekly', label: 'eventsWeekly', hint: 'eventsWeeklyHint' },
  { key: 'digest_news_monthly', label: 'newsMonthly', hint: 'newsMonthlyHint' },
  { key: 'digest_events_monthly', label: 'eventsMonthly', hint: 'eventsMonthlyHint' },
  { key: 'event_reminders', label: 'eventReminders', hint: 'eventRemindersHint' },
]
