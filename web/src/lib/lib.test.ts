import { formatCompactNumber, formatRelative } from './dates'
import { fold, upper } from './greek'

describe('upper', () => {
  it('drops Greek accents when uppercasing', () => {
    expect(upper('Τεχνολογία & Έρευνα')).toBe('ΤΕΧΝΟΛΟΓΙΑ & ΕΡΕΥΝΑ')
    expect(upper('Ημερήσια ενημέρωση')).toBe('ΗΜΕΡΗΣΙΑ ΕΝΗΜΕΡΩΣΗ')
  })

  it('leaves Latin text accents alone', () => {
    expect(upper('Café funding')).toBe('CAFÉ FUNDING')
  })
})

describe('fold', () => {
  it('matches Greek text without accents or case', () => {
    expect(fold('Ναυτιλία')).toBe(fold('ναυτιλια'))
  })
})

describe('formatRelative', () => {
  const now = new Date('2026-10-07T12:00:00Z')

  it('formats in English and Greek', () => {
    expect(formatRelative('2026-10-07T10:00:00Z', 'en', now)).toMatch(/^2\s?h(r)?\.? ago$/)
    expect(formatRelative('2026-10-07T10:00:00Z', 'el', now)).toBe('πριν από 2 ώρες')
    expect(formatRelative('2026-10-06T12:00:00Z', 'en', now)).toBe('yesterday')
  })
})

describe('formatCompactNumber', () => {
  it('formats read counts compactly', () => {
    expect(formatCompactNumber(12400, 'en')).toMatch(/^12\.4K$/i)
    expect(formatCompactNumber(12400, 'el')).toMatch(/12,4/)
  })
})
