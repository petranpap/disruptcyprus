import { useTranslation } from 'react-i18next'
import { IconButton } from '@/components/ui/IconButton'
import { cn } from '@/lib/cn'

interface BookmarkButtonProps {
  saved: boolean
  onToggle?: () => void
  className?: string
  size?: number
}

export function BookmarkButton({ saved, onToggle, className, size = 20 }: BookmarkButtonProps) {
  const { t } = useTranslation()

  return (
    <IconButton
      icon={saved ? 'bookmark-fill' : 'bookmark'}
      label={saved ? t('common.bookmarked') : t('common.bookmark')}
      aria-pressed={saved}
      active={saved}
      size={size}
      onClick={(event) => {
        // Cards are links; saving must not navigate.
        event.preventDefault()
        event.stopPropagation()
        onToggle?.()
      }}
      className={cn('-mr-2', saved && 'text-brand-cyan', className)}
    />
  )
}
