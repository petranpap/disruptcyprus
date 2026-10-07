import { Link, useParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { useDigest, useDigests, useLatestDigest } from '@/api/content'
import { ApiError } from '@/api/errors'
import type { Digest, DigestCadence, DigestKind } from '@/api/schemas'
import { CompactCard } from '@/components/content/CompactCard'
import { EventRow } from '@/components/content/EventCards'
import { Badge } from '@/components/ui/Badge'
import { EmptyState } from '@/components/ui/EmptyState'
import { IconButton } from '@/components/ui/IconButton'
import { Skeleton } from '@/components/ui/Skeleton'
import { QueryError } from '@/components/feedback/QueryError'
import { useShare } from '@/hooks/useShare'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import { upper } from '@/lib/greek'

type DigestItem = Digest['items'][number]

function DigestPeriod({ digest }: { digest: Digest }) {
  const locale = currentLocale()
  const sameDay = digest.period_start === digest.period_end
  const start = formatDate(digest.period_start, locale, { day: 'numeric', month: 'long', year: 'numeric' })

  return (
    <span>
      {sameDay
        ? start
        : `${formatDate(digest.period_start, locale, { day: 'numeric', month: 'long' })} – ${formatDate(digest.period_end, locale, { day: 'numeric', month: 'long', year: 'numeric' })}`}
    </span>
  )
}

function Item({ entry, rank }: { entry: DigestItem; rank: number }) {
  const { t } = useTranslation()

  return (
    <li className="flex gap-3">
      <span
        className={cn(
          'w-8 shrink-0 pt-3 text-right text-2xl font-black tracking-tighter italic',
          rank === 1 ? 'text-brand-crimson' : rank === 2 ? 'text-brand-cyan' : 'text-outline',
        )}
      >
        {rank}
      </span>
      <div className="min-w-0 flex-1 space-y-2">
        {entry.is_highlighted && <Badge variant="category">{t('digest.forYou')}</Badge>}
        {entry.item.type === 'article' ? <CompactCard article={entry.item} /> : <EventRow event={entry.item} />}
        {entry.editor_note && (
          <p className="border-l-2 border-brand-cyan pl-3 font-body text-body-md text-on-surface-variant italic">
            {entry.editor_note}
          </p>
        )}
      </div>
    </li>
  )
}

/** Monthly News is grouped by section; everything else is a single ranked list. */
function groupItems(digest: Digest): { title: string | null; items: DigestItem[] }[] {
  if (!(digest.kind === 'news' && digest.cadence === 'monthly')) return [{ title: null, items: digest.items }]

  const groups = new Map<string, DigestItem[]>()
  for (const entry of digest.items) {
    const name = entry.item.type === 'article' ? entry.item.section.name : ''
    groups.set(name, [...(groups.get(name) ?? []), entry])
  }

  return [...groups.entries()].map(([title, items]) => ({ title, items }))
}

export function DigestBody({ digest, headingLevel = 'h1' }: { digest: Digest; headingLevel?: 'h1' | 'h2' }) {
  const { t } = useTranslation()
  const share = useShare()
  const Heading = headingLevel
  let rank = 0

  return (
    <article className="space-y-space-lg">
      <header className="space-y-2 pb-space-md hairline-b">
        <div className="flex items-center justify-between gap-3">
          <p className="text-label-md font-bold tracking-wider text-secondary">
            {upper(t(`digest.label.${digest.kind}_${digest.cadence}`))}
          </p>
          <IconButton
            icon="share"
            label={t('common.share')}
            size={20}
            onClick={() => void share({ title: digest.title, url: digest.share_url })}
          />
        </div>
        <Heading className="font-headline text-headline-hero-mobile text-on-surface lg:text-headline-hero">
          {digest.title}
        </Heading>
        <p className="text-label-md text-outline">
          <DigestPeriod digest={digest} />
        </p>
        {digest.intro && <p className="font-body text-body-lg text-on-surface-variant">{digest.intro}</p>}
      </header>

      {digest.items.length === 0 ? (
        <EmptyState icon="auto_awesome" title={t('digest.empty')} />
      ) : (
        groupItems(digest).map((group) => (
          <section key={group.title ?? 'all'} className="space-y-space-md">
            {group.title && <h2 className="font-headline text-headline-md text-on-surface">{group.title}</h2>}
            <ol className="space-y-space-md">
              {group.items.map((entry) => (
                <Item key={entry.id} entry={entry} rank={++rank} />
              ))}
            </ol>
          </section>
        ))
      )}
    </article>
  )
}

function DigestSkeleton() {
  return (
    <div className="space-y-space-md" role="status" aria-busy="true">
      <Skeleton className="h-4 w-32" />
      <Skeleton className="h-8 w-3/4" />
      <Skeleton className="h-20 w-full" />
      <Skeleton className="h-24 w-full" />
    </div>
  )
}

function PreviousEditions({
  kind,
  cadence,
  currentSlug,
}: {
  kind: DigestKind
  cadence: DigestCadence
  currentSlug: string
}) {
  const { t, i18n } = useTranslation()
  const list = useDigests(kind, cadence, i18n.language)
  const previous = list.items.filter((digest) => digest.slug !== currentSlug).slice(0, 6)

  if (previous.length === 0) return null

  return (
    <section className="mt-space-xl space-y-3" aria-labelledby="previous-editions">
      <h2 id="previous-editions" className="text-label-md tracking-wider text-outline uppercase">
        {t('digest.previous')}
      </h2>
      <ul className="divide-y divide-hairline rounded-card border border-card-stroke bg-surface-container-lowest">
        {previous.map((digest) => (
          <li key={digest.slug}>
            <Link
              to={`/digests/${digest.slug}`}
              className="flex min-h-tap items-center justify-between gap-3 px-space-md py-3 text-label-lg text-on-surface hover:text-primary"
            >
              <span>{digest.title}</span>
              <span className="text-label-sm text-outline">
                {t('digest.itemsCount', { count: digest.items_count ?? 0 })}
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  )
}

/** Latest published edition of a kind/cadence (News → Daily / Monthly tabs). */
export function LatestDigestView({ kind, cadence }: { kind: DigestKind; cadence: DigestCadence }) {
  const { t, i18n } = useTranslation()
  const digest = useLatestDigest(kind, cadence, i18n.language)

  if (digest.isPending) return <DigestSkeleton />
  if (digest.isError) {
    return digest.error instanceof ApiError && digest.error.status === 404 ? (
      <EmptyState icon="auto_awesome" title={t('digest.notPublished')} />
    ) : (
      <QueryError error={digest.error} onRetry={() => void digest.refetch()} />
    )
  }

  return (
    <>
      <DigestBody digest={digest.data} headingLevel="h2" />
      <PreviousEditions kind={kind} cadence={cadence} currentSlug={digest.data.slug} />
    </>
  )
}

/** /digests/:slug */
export function DigestPage() {
  const { slug = '' } = useParams()
  const { i18n } = useTranslation()
  const digest = useDigest(slug, i18n.language)

  return (
    <div className="mx-auto w-full max-w-reading">
      {digest.isPending ? (
        <DigestSkeleton />
      ) : digest.isError ? (
        <QueryError error={digest.error} onRetry={() => void digest.refetch()} />
      ) : (
        <>
          <DigestBody digest={digest.data} />
          <PreviousEditions kind={digest.data.kind} cadence={digest.data.cadence} currentSlug={digest.data.slug} />
        </>
      )}
    </div>
  )
}
