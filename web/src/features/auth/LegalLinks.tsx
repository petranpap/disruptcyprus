import { Trans } from 'react-i18next'
import { SITE_URL } from '@/lib/env'

/** Renders a translation containing <terms> and <privacy> tags as links to the public legal pages. */
export function LegalText({ i18nKey }: { i18nKey: 'welcome.legal' | 'auth.consent' }) {
  const linkClass = 'underline underline-offset-2 hover:text-on-surface'

  return (
    <Trans
      i18nKey={i18nKey}
      components={{
        terms: <a href={`${SITE_URL}/terms`} target="_blank" rel="noopener noreferrer" className={linkClass} />,
        privacy: <a href={`${SITE_URL}/privacy`} target="_blank" rel="noopener noreferrer" className={linkClass} />,
      }}
    />
  )
}
