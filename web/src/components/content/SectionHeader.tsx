import { Link } from 'react-router'
import { useTranslation } from 'react-i18next'
import { Icon, type IconName } from '@/components/ui/Icon'

interface SectionHeaderProps {
  title: string
  icon?: IconName
  to?: string
  linkLabel?: string
}

export function SectionHeader({ title, icon, to, linkLabel }: SectionHeaderProps) {
  const { t } = useTranslation()

  return (
    <div className="flex items-center justify-between gap-3">
      <h2 className="flex items-center gap-2 font-headline text-headline-md font-bold text-on-surface">
        {icon && <Icon name={icon} size={22} className="text-brand-crimson" />}
        {title}
      </h2>
      {to && (
        <Link to={to} className="inline-flex min-h-tap items-center text-label-md text-primary hover:underline">
          {linkLabel ?? t('common.viewAll')}
          <Icon name="chevron_right" size={16} />
        </Link>
      )}
    </div>
  )
}
