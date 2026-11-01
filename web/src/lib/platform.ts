/**
 * Which device/browser the reader uses, to show the right "install the app" steps.
 * Only Chrome/Edge/Samsung on Android and Chrome/Edge on computers offer a one-tap install prompt;
 * everywhere else the reader adds the app from the browser's own menu.
 */
export type InstallPlatform =
  | 'ios-safari'
  | 'ios-other'
  | 'android-chrome'
  | 'android-samsung'
  | 'android-firefox'
  | 'android-other'
  | 'desktop-chromium'
  | 'desktop-safari'
  | 'desktop-firefox'
  | 'other'

export function detectInstallPlatform(userAgent: string, maxTouchPoints = 0): InstallPlatform {
  const ua = userAgent
  // iPadOS reports itself as a Mac; the touch screen gives it away.
  const ios = /iPad|iPhone|iPod/.test(ua) || (ua.includes('Macintosh') && maxTouchPoints > 1)

  if (ios) return /CriOS|FxiOS|EdgiOS|OPiOS/.test(ua) ? 'ios-other' : 'ios-safari'

  if (/Android/.test(ua)) {
    if (/SamsungBrowser/.test(ua)) return 'android-samsung'
    if (/Firefox/.test(ua)) return 'android-firefox'
    if (/Chrome|EdgA/.test(ua)) return 'android-chrome'
    return 'android-other'
  }

  if (/Firefox/.test(ua)) return 'desktop-firefox'
  if (/Edg\/|Chrome\//.test(ua)) return 'desktop-chromium'
  if (/Macintosh/.test(ua) && /Safari/.test(ua)) return 'desktop-safari'

  return 'other'
}

export function currentInstallPlatform(): InstallPlatform {
  return detectInstallPlatform(navigator.userAgent, navigator.maxTouchPoints ?? 0)
}

/** Platforms offered under "Installing on another device?" (the two that matter for phones). */
export const OTHER_DEVICE_PLATFORMS: InstallPlatform[] = ['ios-safari', 'android-chrome']
