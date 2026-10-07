import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router'
import { useTranslation } from 'react-i18next'
import type { Industry, MyIndustries } from '@/api/schemas'
import { useIndustries, useMyIndustries, useUpdateMyIndustries } from '@/api/taxonomy'
import { BottomDock } from '@/components/layout/BottomDock'
import { StepHeader } from '@/components/layout/StepHeader'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon } from '@/components/ui/Icon'
import { Sheet } from '@/components/ui/Sheet'
import { Skeleton } from '@/components/ui/Skeleton'
import { FormAlert } from '@/features/auth/AuthHeading'
import { applyServerErrors } from '@/lib/forms'
import { fold, upper } from '@/lib/greek'
import { GROUP_ORDER, MIN_INDUSTRIES } from './constants'
import { OnboardingIntro } from './OnboardingIntro'
import { TopicTile } from './TopicTile'

/** Waits for the reader's current industries so the form starts from them. */
export function IndustriesStep() {
  const mine = useMyIndustries()

  if (mine.isPending) {
    return (
      <>
        <StepHeader current={2} total={3} />
        <div className="mx-auto grid w-full max-w-narrow grid-cols-2 gap-3.5 px-space-md pt-space-xl sm:grid-cols-3 lg:grid-cols-4 lg:px-8">
          {Array.from({ length: 6 }, (_, index) => (
            <Skeleton key={index} className="h-32 rounded-card" />
          ))}
        </div>
      </>
    )
  }

  return <IndustriesForm initial={mine.data ?? { industry_ids: [], notify_ids: [] }} />
}

function IndustriesForm({ initial }: { initial: MyIndustries }) {
  const { t, i18n } = useTranslation()
  const navigate = useNavigate()
  const industries = useIndustries(i18n.language)
  const update = useUpdateMyIndustries()

  const [selected, setSelected] = useState<Set<number>>(() => new Set(initial.industry_ids))
  const [query, setQuery] = useState('')
  const [skipOpen, setSkipOpen] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)

  const groups = useMemo(() => {
    const needle = fold(query.trim())
    const visible = (industries.data ?? []).filter(
      (industry) => !needle || fold(industry.name).includes(needle) || industry.slug.includes(needle),
    )

    return GROUP_ORDER.map((group) => ({
      group,
      items: visible.filter((industry) => industry.group === group),
    })).filter((entry) => entry.items.length > 0)
  }, [industries.data, query])

  const toggle = (industry: Industry) =>
    setSelected((current) => {
      const next = new Set(current)
      if (next.has(industry.id)) next.delete(industry.id)
      else next.add(industry.id)
      return next
    })

  const total = industries.data?.length ?? 0
  const count = selected.size
  const remaining = Math.max(0, MIN_INDUSTRIES - count)

  const submit = async () => {
    setFormError(null)
    const industryIds = [...selected]
    try {
      await update.mutateAsync({
        industry_ids: industryIds,
        // Keep alert choices for industries that stay selected.
        notify_ids: initial.notify_ids.filter((id) => selected.has(id)),
      })
      navigate('/onboarding/notifications')
    } catch (error) {
      setFormError(applyServerErrors(error, () => undefined, []))
    }
  }

  return (
    <>
      <StepHeader
        current={2}
        total={3}
        onBack={() => navigate('/onboarding/account')}
        onSkip={() => setSkipOpen(true)}
      />
      <main id="main" className="mx-auto w-full max-w-narrow px-space-md pt-space-lg pb-36 lg:px-8 lg:pt-space-xl">
        <OnboardingIntro
          icon="tune"
          kicker={t('onboarding.industries.kicker')}
          title={t('onboarding.industries.title')}
          subtitle={t('onboarding.industries.subtitle')}
        />

        <label className="relative mb-space-lg block">
          <span className="sr-only">{t('onboarding.industries.search')}</span>
          <Icon
            name="search"
            size={20}
            className="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-outline"
          />
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={t('onboarding.industries.search')}
            className="h-11 w-full rounded-pill border border-outline-variant/70 bg-surface-container-lowest pr-4 pl-11 text-body-sm text-on-surface placeholder:text-outline focus:border-brand-cyan focus:ring-2 focus:ring-brand-cyan/25 focus:outline-none"
          />
        </label>

        {formError && <FormAlert>{formError}</FormAlert>}

        {industries.isPending ? (
          <div className="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-4">
            {Array.from({ length: 6 }, (_, index) => (
              <Skeleton key={index} className="h-32 rounded-card" />
            ))}
          </div>
        ) : industries.isError ? (
          <EmptyState
            icon="error"
            tone="error"
            title={t('errors.generic')}
            action={
              <Button variant="outline" onClick={() => void industries.refetch()}>
                {t('common.retry')}
              </Button>
            }
          />
        ) : groups.length === 0 ? (
          <EmptyState icon="search" title={t('onboarding.industries.noMatch', { query })} />
        ) : (
          <div className="flex flex-col gap-space-xl">
            {groups.map(({ group, items }) => (
              <section key={group} aria-labelledby={`group-${group}`}>
                <h2 id={`group-${group}`} className="mb-3 text-label-md tracking-wider text-on-surface-variant">
                  {upper(t(`onboarding.industries.groups.${group}`))}
                </h2>
                <div className="grid grid-cols-2 gap-3.5 sm:grid-cols-3 lg:grid-cols-4">
                  {items.map((industry) => (
                    <TopicTile
                      key={industry.id}
                      industry={industry}
                      selected={selected.has(industry.id)}
                      onToggle={toggle}
                    />
                  ))}
                </div>
              </section>
            ))}
          </div>
        )}

        <p className="mt-6 flex items-center justify-center gap-1.5 text-center text-label-md text-on-surface-variant">
          <Icon name="auto_awesome" size={16} className="text-primary" />
          {t('onboarding.industries.helper')}
        </p>
      </main>

      <BottomDock>
        <div className="flex flex-col pl-1">
          <span className="text-label-sm tracking-wide text-on-surface-variant">
            {upper(t('onboarding.industries.selection'))}
          </span>
          <span className="font-headline text-headline-sm whitespace-nowrap text-on-surface" aria-live="polite">
            {t('onboarding.industries.chosen', { count, total })}
          </span>
        </div>
        <Button
          block={false}
          className="flex-1 sm:max-w-80"
          disabled={remaining > 0}
          loading={update.isPending}
          onClick={() => void submit()}
          trailingIcon={<Icon name="arrow_forward" size={18} />}
        >
          {remaining > 0
            ? t('onboarding.industries.selectMore', { count: remaining })
            : t('onboarding.industries.continueCount', { count })}
        </Button>
      </BottomDock>

      <Sheet open={skipOpen} onClose={() => setSkipOpen(false)} title={t('onboarding.industries.skipTitle')}>
        <p className="mb-space-lg font-body text-body-md text-on-surface-variant">
          {t('onboarding.industries.skipBody')}
        </p>
        <div className="flex flex-col gap-3">
          <Button onClick={() => setSkipOpen(false)}>{t('onboarding.industries.keepChoosing')}</Button>
          <Button variant="outline" onClick={() => navigate('/onboarding/notifications')}>
            {t('onboarding.industries.skipConfirm')}
          </Button>
        </div>
      </Sheet>
    </>
  )
}
