import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useMe } from '@/api/auth'
import { useIndustryList } from '@/api/content'
import { useMyIndustries } from '@/api/taxonomy'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { Chip } from '@/components/ui/Chip'
import { Sheet } from '@/components/ui/Sheet'

interface IndustryChipsProps {
  value: string[]
  onChange: (next: string[]) => void
}

const VISIBLE_FOR_GUESTS = 8

/**
 * Filter row under the section tabs: "All", the reader's followed industries (or popular ones for guests),
 * any active filters, and "More" for the full list of 33.
 */
export function IndustryChips({ value, onChange }: IndustryChipsProps) {
  const { t, i18n } = useTranslation()
  const { data: user } = useMe()
  const industries = useIndustryList(i18n.language)
  const mine = useMyIndustries(Boolean(user))
  const [sheetOpen, setSheetOpen] = useState(false)
  const [draft, setDraft] = useState<string[]>([])

  const chips = useMemo(() => {
    const all = industries.data ?? []
    const followedIds = new Set(mine.data?.industry_ids ?? [])
    const base =
      user && followedIds.size > 0
        ? all.filter((industry) => followedIds.has(industry.id))
        : all.slice(0, VISIBLE_FOR_GUESTS)
    const extra = all.filter((industry) => value.includes(industry.slug) && !base.includes(industry))

    return [...extra, ...base]
  }, [industries.data, mine.data, user, value])

  const toggle = (slug: string) =>
    onChange(value.includes(slug) ? value.filter((item) => item !== slug) : [...value, slug])

  return (
    <>
      <div
        className="-mx-margin no-scrollbar flex gap-2 overflow-x-auto px-margin pb-1 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0"
        role="group"
        aria-label={t('filters.industries')}
      >
        <Chip selected={value.length === 0} onClick={() => onChange([])}>
          {t('filters.all')}
        </Chip>
        {chips.map((industry) => (
          <Chip
            key={industry.slug}
            selected={value.includes(industry.slug)}
            color={industry.color}
            onClick={() => toggle(industry.slug)}
          >
            {industry.name}
          </Chip>
        ))}
        <Chip
          onClick={() => {
            setDraft(value)
            setSheetOpen(true)
          }}
        >
          {t('filters.more')}
        </Chip>
      </div>

      <Sheet open={sheetOpen} onClose={() => setSheetOpen(false)} title={t('filters.industries')}>
        <div className="max-h-[50dvh] overflow-y-auto">
          {(industries.data ?? []).map((industry) => (
            <Checkbox
              key={industry.slug}
              label={industry.name}
              checked={draft.includes(industry.slug)}
              onChange={() =>
                setDraft((current) =>
                  current.includes(industry.slug)
                    ? current.filter((item) => item !== industry.slug)
                    : [...current, industry.slug],
                )
              }
            />
          ))}
        </div>
        <div className="mt-space-md flex gap-3">
          <Button variant="outline" onClick={() => setDraft([])}>
            {t('filters.clear')}
          </Button>
          <Button
            onClick={() => {
              onChange(draft)
              setSheetOpen(false)
            }}
          >
            {t('filters.apply')}
          </Button>
        </div>
      </Sheet>
    </>
  )
}
