// Applies the saved/system theme before first paint (no light flash in dark mode).
// A separate file, not inline, so the Content-Security-Policy can forbid inline scripts.
;(function () {
  try {
    // ?theme=dark|light overrides for QA and screenshots.
    var forced = new URLSearchParams(location.search).get('theme')
    var saved = forced === 'dark' || forced === 'light' ? forced : localStorage.getItem('dc.theme')
    var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches
    document.documentElement.dataset.theme = dark ? 'dark' : 'light'
    var locale = localStorage.getItem('dc.locale')
    if (locale === 'en' || locale === 'el') document.documentElement.lang = locale
  } catch (e) {
    document.documentElement.dataset.theme = 'light'
  }
})()
