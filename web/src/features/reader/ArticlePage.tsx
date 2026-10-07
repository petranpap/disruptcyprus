import { useEffect, useMemo, useRef } from 'react'
import { useParams } from 'react-router'
import { useTranslation } from 'react-i18next'
import { recordArticleView, useArticle, useRelatedArticles } from '@/api/content'
import type { Article } from '@/api/schemas'
import { BookmarkButton } from '@/components/content/BookmarkButton'
import { SectionHeader } from '@/components/content/SectionHeader'
import { StandardCard } from '@/components/content/StandardCard'
import { DetailShell } from '@/components/layout/DetailShell'
import { QueryError } from '@/components/feedback/QueryError'
import { Badge } from '@/components/ui/Badge'
import { Icon } from '@/components/ui/Icon'
import { IconButton } from '@/components/ui/IconButton'
import { Skeleton } from '@/components/ui/Skeleton'
import { useBookmark } from '@/hooks/useBookmark'
import { useShare } from '@/hooks/useShare'
import { useSpeech } from '@/hooks/useSpeech'
import { currentLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import { htmlToSpeechText } from '@/lib/tts'
import { ReaderToolbar } from './ReaderToolbar'
import { useReaderFontSize } from './useReaderFontSize'

function ListenControls({ article }: { article: Article }) {
  const { t } = useTranslation()
  const speech = useSpeech(article.locale)
  const text = useMemo(() => `${article.title ?? ''}. ${htmlToSpeechText(article.body)}`, [article.title, article.body])

  if (!speech.available) return null

  return (
    <>
      <button
        type="button"
        onClick={() =>
          speech.state === 'idle' ? speech.play(text) : speech.state === 'playing' ? speech.pause() : speech.resume()
        }
        className="group inline-flex min-h-tap items-center gap-2 self-start rounded-pill border border-outline-variant/40 bg-surface-container-lowest px-3.5 py-1.5 shadow-sm transition-all hover:border-brand-cyan active:scale-95 sm:self-center"
      >
        <span className="flex size-6 items-center justify-center rounded-pill bg-brand-cyan text-white">
          <Icon name="play_arrow-fill" size={16} />
        </span>
        <span className="text-label-md text-on-surface group-hover:text-primary">
          {speech.state === 'playing'
            ? t('reader.pause')
            : speech.state === 'paused'
              ? t('reader.resume')
              : t('reader.listen', { count: article.reading_time_minutes ?? 1 })}
        </span>
      </button>
      {speech.state !== 'idle' && (
        <div
          role="region"
          aria-label={t('reader.player')}
          className="fixed inset-x-0 bottom-0 z-50 glass pb-safe shadow-float hairline-t"
        >
          <div className="h-[2px] bg-outline-variant/20">
            <div className="h-full bg-brand-cyan" style={{ width: `${speech.progress * 100}%` }} />
          </div>
          <div className="mx-auto flex max-w-reading items-center gap-3 px-space-md py-2 lg:px-8">
            <IconButton
              icon={speech.state === 'playing' ? 'close' : 'play_arrow-fill'}
              label={speech.state === 'playing' ? t('reader.stop') : t('reader.resume')}
              onClick={() => (speech.state === 'playing' ? speech.stop() : speech.resume())}
            />
            <p className="min-w-0 flex-1 truncate font-headline text-body-sm font-semibold text-on-surface">
              {article.title}
            </p>
          </div>
        </div>
      )}
    </>
  )
}

function ReaderSkeleton() {
  return (
    <div
      className="mx-auto w-full max-w-reading space-y-space-md px-space-md pt-space-sm lg:px-8"
      role="status"
      aria-busy="true"
    >
      <Skeleton className="aspect-[16/10] w-full rounded-b-hero-bottom" />
      <Skeleton className="h-5 w-40" />
      <Skeleton className="h-9 w-full" />
      <Skeleton className="h-9 w-2/3" />
      <Skeleton className="h-40 w-full" />
    </div>
  )
}

function ArticleContent({ article, fontSizeClass }: { article: Article; fontSizeClass: string }) {
  const { t, i18n } = useTranslation()
  const related = useRelatedArticles(article.slug, i18n.language)
  const locale = currentLocale()

  return (
    <article className="mx-auto w-full max-w-reading px-space-md pt-space-sm lg:px-8" lang={article.locale}>
      <figure className="relative aspect-[16/10] w-full overflow-hidden rounded-t-control rounded-b-hero-bottom bg-surface-container-high shadow-card sm:aspect-[16/9]">
        {article.image && (
          <img src={article.image.hero} alt="" className="size-full object-cover" fetchPriority="high" />
        )}
        <div
          className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"
          aria-hidden="true"
        />
        {article.hero_caption && (
          <figcaption className="absolute right-3 bottom-3 rounded-pill bg-black/60 px-2.5 py-1 text-label-sm text-white/90 backdrop-blur-md">
            {article.hero_caption}
          </figcaption>
        )}
      </figure>

      <header className="mt-space-lg space-y-space-sm">
        <div className="flex flex-wrap items-center gap-2">
          {article.primary_industry && <Badge>{article.primary_industry.name}</Badge>}
          {article.is_original && (
            <Badge variant="original-soft" dot>
              {t('common.disruptOriginal')}
            </Badge>
          )}
        </div>
        {article.is_fallback && (
          <p
            className="rounded-control bg-surface-container-low px-3 py-2 text-body-sm text-on-surface-variant"
            lang={i18n.language}
          >
            {t('reader.fallbackNotice', { language: t(`language.${article.locale}`) })}
          </p>
        )}
        <h1 className="pt-1 font-headline text-headline-hero-mobile text-balance text-on-surface sm:text-headline-hero">
          {article.title}
        </h1>
        {article.excerpt && <p className="font-body text-body-md text-on-surface-variant">{article.excerpt}</p>}

        <div className="flex flex-col justify-between gap-4 border-y border-outline-variant/30 py-space-md sm:flex-row sm:items-center">
          <div className="flex items-center gap-3">
            <span className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-pill border border-outline-variant/50 bg-surface-container">
              {article.author.avatar_url ? (
                <img src={article.author.avatar_url} alt="" className="size-full object-cover" />
              ) : (
                <Icon name="person" size={22} className="text-outline" />
              )}
            </span>
            <div>
              <p className="flex items-center gap-1.5 text-label-lg font-bold text-on-surface">
                {article.author.name}
                {article.author.is_verified && (
                  <Icon name="verified-fill" size={15} className="text-primary" label={t('reader.verified')} />
                )}
              </p>
              <p className="text-label-sm text-on-surface-variant" lang={i18n.language}>
                {[
                  article.author.title,
                  article.published_at && formatDate(article.published_at, locale, { day: 'numeric', month: 'short' }),
                  article.reading_time_minutes && t('common.minRead', { count: article.reading_time_minutes }),
                ]
                  .filter(Boolean)
                  .join(' · ')}
              </p>
            </div>
          </div>
          <ListenControls article={article} />
        </div>
      </header>

      <div
        className={cn('reader-body mt-space-lg', fontSizeClass)}
        dangerouslySetInnerHTML={{ __html: article.body }}
      />

      {article.attachment && (
        <a
          href={article.attachment.url}
          download
          className="mt-space-xl flex items-center gap-3 rounded-card border border-card-stroke bg-surface-container-lowest p-space-md transition-colors hover:border-brand-cyan"
        >
          <span className="flex size-10 items-center justify-center rounded-control bg-primary-fixed text-primary">
            <Icon name="arrow_forward" size={20} className="rotate-90" />
          </span>
          <span className="min-w-0 flex-1">
            <span className="block text-label-lg text-on-surface">{t('reader.attachment')}</span>
            <span className="block truncate text-body-sm text-on-surface-variant">
              {article.attachment.file_name} · {(article.attachment.size / 1024 / 1024).toFixed(1)} MB
            </span>
          </span>
        </a>
      )}

      {related.data && related.data.length > 0 && (
        <section
          className="mt-space-xl mb-space-lg space-y-space-md"
          aria-labelledby="related-title"
          lang={i18n.language}
        >
          <div>
            <p className="text-label-sm font-bold tracking-wider text-primary uppercase">{t('reader.relatedKicker')}</p>
            <div id="related-title">
              <SectionHeader title={t('reader.related')} />
            </div>
          </div>
          <div className="-mx-space-md no-scrollbar flex snap-x gap-4 overflow-x-auto px-space-md pb-2 lg:mx-0 lg:grid lg:grid-cols-2 lg:overflow-visible lg:px-0">
            {related.data.map((item) => (
              <div key={item.id} className="w-72 flex-shrink-0 snap-start lg:w-auto">
                <StandardCard article={item} />
              </div>
            ))}
          </div>
        </section>
      )}
    </article>
  )
}

/** /articles/:slug */
export function ArticlePage() {
  const { slug = '' } = useParams()
  const { t, i18n } = useTranslation()
  const article = useArticle(slug, i18n.language)
  const bookmark = useBookmark()
  const share = useShare()
  const viewed = useRef<number | null>(null)
  const fontSize = useReaderFontSize()

  useEffect(() => {
    if (article.data && viewed.current !== article.data.id) {
      viewed.current = article.data.id
      recordArticleView(article.data.id)
    }
  }, [article.data])

  const actions = article.data && (
    <>
      <button
        type="button"
        onClick={fontSize.cycle}
        aria-label={t('reader.textSizeStep', { step: fontSize.step + 1 })}
        className="inline-flex size-tap items-center justify-center rounded-pill text-on-surface-variant transition-colors hover:text-primary active:scale-95"
      >
        <span className="text-label-md font-bold tracking-tight">
          A<span className="relative -top-1 text-[9px] font-semibold">+</span>
        </span>
      </button>
      <IconButton
        icon="share"
        label={t('common.share')}
        size={20}
        onClick={() => article.data && void share({ title: article.data.title ?? '', url: article.data.share_url })}
      />
      <BookmarkButton
        saved={article.data.is_bookmarked}
        onToggle={() => article.data && bookmark(article.data)}
        size={22}
        className="mr-0"
      />
    </>
  )

  return (
    <DetailShell toolbar={<ReaderToolbar actions={actions} />}>
      {article.isPending ? (
        <ReaderSkeleton />
      ) : article.isError ? (
        <div className="mx-auto max-w-reading px-space-md">
          <QueryError error={article.error} onRetry={() => void article.refetch()} />
        </div>
      ) : (
        <ArticleContent article={article.data} fontSizeClass={fontSize.className} />
      )}
    </DetailShell>
  )
}
