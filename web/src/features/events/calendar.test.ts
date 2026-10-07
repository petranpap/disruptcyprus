import { addDays, monthGrid, shiftMonth } from './calendar'

it('builds Monday-first weeks for a month', () => {
  const weeks = monthGrid('2026-10')

  expect(weeks[0]).toEqual([null, null, null, '2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04'])
  expect(weeks.at(-1)?.filter(Boolean).at(-1)).toBe('2026-10-31')
  expect(weeks.every((week) => week.length === 7)).toBe(true)
})

it('shifts months and days across year boundaries', () => {
  expect(shiftMonth('2026-12', 1)).toBe('2027-01')
  expect(shiftMonth('2026-01', -1)).toBe('2025-12')
  expect(addDays('2026-12-31', 1)).toBe('2027-01-01')
})
