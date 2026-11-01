import { formatCompactNumber, formatRelative } from './dates'
import { fold, upper } from './greek'
import { siteUrlFor } from './env'
import { detectInstallPlatform } from './platform'

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

describe('siteUrlFor', () => {
  it('derives the public site from the app address, never from a hardcoded domain', () => {
    expect(siteUrlFor({ protocol: 'https:', host: 'app.disruptcyprus.com' })).toBe('https://disruptcyprus.com')
    expect(siteUrlFor({ protocol: 'https:', host: 'app.staging.example.org' })).toBe('https://staging.example.org')
    expect(siteUrlFor({ protocol: 'http:', host: 'localhost:5173' }, 'http://localhost:8080/')).toBe(
      'http://localhost:8080',
    )
  })
})

describe('detectInstallPlatform', () => {
  it.each([
    [
      'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1',
      0,
      'ios-safari',
    ],
    [
      'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 CriOS/130.0 Mobile/15E148 Safari/604.1',
      0,
      'ios-other',
    ],
    [
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/18.0 Safari/605.1.15',
      5,
      'ios-safari',
    ],
    [
      'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36',
      5,
      'android-chrome',
    ],
    [
      'Mozilla/5.0 (Linux; Android 14; SM-S921B) AppleWebKit/537.36 SamsungBrowser/26.0 Chrome/122.0 Mobile Safari/537.36',
      5,
      'android-samsung',
    ],
    ['Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0', 5, 'android-firefox'],
    [
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36 Edg/130.0',
      0,
      'desktop-chromium',
    ],
    [
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/18.0 Safari/605.1.15',
      0,
      'desktop-safari',
    ],
    ['Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0', 0, 'desktop-firefox'],
  ])('%s (%i touch points) → %s', (ua, touch, expected) => {
    expect(detectInstallPlatform(ua, touch)).toBe(expected)
  })
})
