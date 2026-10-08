import { useEffect, useState } from 'react'
import { useLocation } from 'react-router'
import { useTranslation } from 'react-i18next'
import { socialRedirectUrl } from '@/api/client'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Spinner } from '@/components/ui/Spinner'

/**
 * Full-page navigation into the OAuth flow (not fetch): the session is created on the callback.
 * Returns the reader to the page that sent them to sign in (e.g. a shared article).
 */
export function GoogleButton() {
  const { t } = useTranslation()
  const location = useLocation()
  const [leaving, setLeaving] = useState(false)
  const from = (location.state as { from?: string } | null)?.from

  // Coming back with the browser's Back button restores this page from the cache: drop the spinner.
  useEffect(() => {
    const reset = (event: PageTransitionEvent) => {
      if (event.persisted) setLeaving(false)
    }
    window.addEventListener('pageshow', reset)

    return () => window.removeEventListener('pageshow', reset)
  }, [])

  return (
    <a
      href={socialRedirectUrl('google', from)}
      className={buttonClasses('social')}
      aria-busy={leaving || undefined}
      onClick={(event) => {
        // A second tap while Google loads would start a second OAuth flow with a different state.
        if (leaving) event.preventDefault()
        setLeaving(true)
      }}
    >
      {leaving ? (
        <Spinner size={16} />
      ) : (
        <svg className="size-4" viewBox="0 0 24 24" aria-hidden="true">
          <path
            d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.67v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.16z"
            fill="#4285F4"
          />
          <path
            d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.26v3.14C3.25 21.31 7.31 24 12 24z"
            fill="#34A853"
          />
          <path
            d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.14-1.55.38-2.27V6.59H1.26C.46 8.18 0 9.99 0 12s.46 3.82 1.26 5.41l4.02-3.14z"
            fill="#FBBC05"
          />
          <path
            d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.25 2.69 1.26 6.59l4.02 3.14c.95-2.83 3.6-4.98 6.72-4.98z"
            fill="#EA4335"
          />
        </svg>
      )}
      <span>{t('welcome.google')}</span>
    </a>
  )
}
