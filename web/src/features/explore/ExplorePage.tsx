import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useIndustryList, useSearch } from '@/api/content'
import type { Industry, IndustryGroup } from '@/api/schemas'
import { CompactCard } from '@/components/content/CompactCard'
import { EventRow } from '@/components/content/EventCards'
import { FeedLayout } from '@/components/content/FeedLayout'
import { TrendingSidebar, UpcomingEventsSidebar } from '@/components/content/Sidebar'
import { QueryError } from '@/components/feedback/QueryError'
import { EmptyState } from '@/components/ui/EmptyState'
import { Icon } from '@/components/ui/Icon'
import { Skeleton } from '@/components/ui/Skeleton'
import { Spinner } from '@/components/ui/Spinner'
import { GROUP_ORDER } from '@/features/onboarding/constants'
import { useDebouncedValue } from '@/hooks/useDebouncedValue'
import { upper } from '@/lib/greek'

function IndustryTile({ industry }: { industry: Industry }) {
  return (
    <Link
      to={`/explore/${industry.slug}`}
      className="group relative flex h-24 press items-end overflow-hidden rounded-card p-3 transition-transform"
      style={
        industry.image_url
          ? undefined
          : {
              backgroundImage: `radial-gradient(circle at 80% 15%, rgb(255 255 255 / 0.22), transparent 45%), linear-gradient(135deg, ${industry.color}, #0F172A)`,
            }
      }
    >
      {industry.image_url && (
        <img src={industry.image_url} alt="" loading="lazy" className="absolute inset-0 size-full object-cover" />
      )}
      <span className="absolute inset-0 scrim-tile" aria-hidden="true" />
      <span className="relative font-headline text-headline-sm leading-tight text-white">{industry.name}</span>
    </Link>
  )
}

function IndustriesDirectory() {
  const { t, i18n } = useTranslation()
  const industries = useIndustryList(i18n.language)

  if (industries.isPending) {
    return (
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {Array.from({ length: 6 }, (_, index) => (
          <Skeleton key={index} className="h-24 rounded-card" />
        ))}
      </div>
    )
  }
  if (industries.isError) return <QueryError error={industries.error} onRetry={() => void industries.refetch()} />

  const groups = GROUP_ORDER.map((group: IndustryGroup) => ({
    group,
    items: industries.data.filter((industry) => industry.group === group),
  }))

  return (
    <div className="space-y-space-xl">
      {groups.map(({ group, items }) => (
        <section key={group} aria-labelledby={`explore-${group}`} className="space-y-3">
          <h2 id={`explore-${group}`} className="text-label-md tracking-wider text-on-surface-variant">
            {upper(t(`onboarding.industries.groups.${group}`))}
          </h2>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {items.map((industry) => (
              <IndustryTile key={industry.id} industry={industry} />
            ))}
          </div>
        </section>
      ))}
    </div>
  )
}

function SearchResults({ query }: { query: string }) {
  const { t, i18n } = useTranslation()
  const results = useSearch(query, i18n.language)

  if (query.trim().length < 2) return <p className="text-body-sm text-outline">{t('explore.minChars')}</p>
  if (results.isPending) return <Skeleton className="h-40" />
  if (results.isError) return <QueryError error={results.error} onRetry={() => void results.refetch()} />

  const { articles, events, industries } = results.data
  if (articles.length + events.length + industries.length === 0)
    return <EmptyState icon="search" title={t('explore.noResults', { query })} />

  return (
    <div className="space-y-space-xl" aria-live="polite">
      {industries.length > 0 && (
        <section className="space-y-3">
          <h2 className="text-label-md tracking-wider text-outline uppercase">{t('explore.industries')}</h2>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {industries.map((industry) => (
              <IndustryTile key={industry.id} industry={industry} />
            ))}
          </div>
        </section>
      )}
      {articles.length > 0 && (
        <section className="space-y-space-sm">
          <h2 className="text-label-md tracking-wider text-outline uppercase">{t('explore.stories')}</h2>
          {articles.map((article) => (
            <CompactCard key={article.id} article={article} />
          ))}
        </section>
      )}
      {events.length > 0 && (
        <section className="space-y-space-sm">
          <h2 className="text-label-md tracking-wider text-outline uppercase">{t('tabs.events')}</h2>
          {events.map((event) => (
            <EventRow key={event.id} event={event} />
          ))}
        </section>
      )}
    </div>
  )
}

/** /explore — search (?q=, ?focus=search) and the industries directory. */
export function ExplorePage() {
  const { t, i18n } = useTranslation()
  const [params, setParams] = useSearchParams()
  const [text, setText] = useState(params.get('q') ?? '')
  const query = useDebouncedValue(text.trim(), 300)
  const input = useRef<HTMLInputElement>(null)
  const searching = useSearch(query, i18n.language).isFetching

  useEffect(() => {
    if (params.get('focus') === 'search') input.current?.focus()
  }, [params])

  useEffect(() => {
    setParams(
      (current) => {
        const next = new URLSearchParams(current)
        next.delete('focus')
        if (query) next.set('q', query)
        else next.delete('q')
        return next
      },
      { replace: true },
    )
  }, [query, setParams])

  const sidebar = useMemo(
    () => (
      <>
        <TrendingSidebar />
        <UpcomingEventsSidebar />
      </>
    ),
    [],
  )

  return (
    <FeedLayout sidebar={sidebar}>
      <div className="space-y-space-lg">
        <h1 className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">
          {t('explore.title')}
        </h1>
        <form role="search" onSubmit={(event) => event.preventDefault()} className="relative">
          <label htmlFor="explore-search" className="sr-only">
            {t('nav.search')}
          </label>
          <Icon
            name="search"
            size={20}
            className="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-outline"
          />
          <input
            ref={input}
            id="explore-search"
            type="search"
            value={text}
            onChange={(event) => setText(event.target.value)}
            placeholder={t('nav.searchPlaceholder')}
            className="h-12 w-full rounded-pill border border-outline-variant/70 bg-surface-container-lowest pr-12 pl-11 text-body-md text-on-surface placeholder:text-outline focus:border-brand-cyan focus:ring-2 focus:ring-brand-cyan/25 focus:outline-none"
          />
          {searching && <Spinner size={18} className="absolute top-1/2 right-4 -translate-y-1/2 text-brand-cyan" />}
        </form>
        {text.trim() ? <SearchResults query={query} /> : <IndustriesDirectory />}
      </div>
    </FeedLayout>
  )
}
