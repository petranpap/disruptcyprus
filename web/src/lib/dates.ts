export type AppLocale = 'el' | 'en'

const DIVISIONS: { amount: number; unit: Intl.RelativeTimeFormatUnit }[] = [
  { amount: 60, unit: 'second' },
  { amount: 60, unit: 'minute' },
  { amount: 24, unit: 'hour' },
  { amount: 7, unit: 'day' },
  { amount: 4.34524, unit: 'week' },
  { amount: 12, unit: 'month' },
  { amount: Number.POSITIVE_INFINITY, unit: 'year' },
]

const intlLocale = (locale: AppLocale) => (locale === 'el' ? 'el-GR' : 'en-GB')

/**
 * "2h ago" (English, narrow, as in the designs) / "πριν από 2 ώρες" (Greek, long: the short Greek form abbreviates to "ώ.").
 */
export function formatRelative(date: Date | string, locale: AppLocale, now: Date = new Date()): string {
  const formatter = new Intl.RelativeTimeFormat(intlLocale(locale), {
    numeric: 'auto',
    style: locale === 'el' ? 'long' : 'narrow',
  })
  let duration = (new Date(date).getTime() - now.getTime()) / 1000

  for (const division of DIVISIONS) {
    if (Math.abs(duration) < division.amount) {
      return formatter.format(Math.round(duration), division.unit)
    }
    duration /= division.amount
  }

  return formatter.format(Math.round(duration), 'year')
}

export function formatDate(
  date: Date | string,
  locale: AppLocale,
  options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' },
  timeZone?: string,
): string {
  return new Intl.DateTimeFormat(intlLocale(locale), { ...options, timeZone }).format(new Date(date))
}

/**
 * Compact counts for "12.4k Reads" / "12,4 χιλ.".
 */
export function formatCompactNumber(value: number, locale: AppLocale): string {
  return new Intl.NumberFormat(intlLocale(locale), { notation: 'compact', maximumFractionDigits: 1 }).format(value)
}
