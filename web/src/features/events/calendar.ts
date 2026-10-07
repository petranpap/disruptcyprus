/** Date-only helpers for the month grid (strings in YYYY-MM-DD / YYYY-MM, no timezone math). */

export function currentMonth(timeZone = 'Asia/Nicosia', now = new Date()): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone, year: 'numeric', month: '2-digit' }).format(now).slice(0, 7)
}

export function today(timeZone = 'Asia/Nicosia', now = new Date()): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(now)
}

export function shiftMonth(month: string, delta: number): string {
  const [year = 0, monthIndex = 1] = month.split('-').map(Number)
  const date = new Date(Date.UTC(year, monthIndex - 1 + delta, 1))

  return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`
}

/** Monday-first weeks covering the month; days outside the month are null. */
export function monthGrid(month: string): (string | null)[][] {
  const [year = 0, monthIndex = 1] = month.split('-').map(Number)
  const first = new Date(Date.UTC(year, monthIndex - 1, 1))
  const daysInMonth = new Date(Date.UTC(year, monthIndex, 0)).getUTCDate()
  const leading = (first.getUTCDay() + 6) % 7
  const cells: (string | null)[] = Array.from({ length: leading }, () => null)

  for (let day = 1; day <= daysInMonth; day++) cells.push(`${month}-${String(day).padStart(2, '0')}`)
  while (cells.length % 7 !== 0) cells.push(null)

  const weeks: (string | null)[][] = []
  for (let index = 0; index < cells.length; index += 7) weeks.push(cells.slice(index, index + 7))

  return weeks
}

/** The event's local calendar day (events are listed under the day they start, in their own timezone). */
export function eventDay(startsAt: string, timeZone: string): string {
  return today(timeZone, new Date(startsAt))
}

export function addDays(day: string, delta: number): string {
  const [year = 0, month = 1, date = 1] = day.split('-').map(Number)
  const next = new Date(Date.UTC(year, month - 1, date + delta))

  return next.toISOString().slice(0, 10)
}
