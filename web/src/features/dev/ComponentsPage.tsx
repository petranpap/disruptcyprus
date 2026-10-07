import { useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import tokens from '@/styles/tokens.json'
import { CompactCardSkeleton, HeroCardSkeleton, StandardCardSkeleton } from '@/components/content/CardSkeletons'
import { CompactCard } from '@/components/content/CompactCard'
import { HeroCard } from '@/components/content/HeroCard'
import { SectionHeader } from '@/components/content/SectionHeader'
import { StandardCard } from '@/components/content/StandardCard'
import { TrendingCard } from '@/components/content/TrendingCard'
import { Screen } from '@/components/layout/Screen'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { Chip } from '@/components/ui/Chip'
import { EmptyState } from '@/components/ui/EmptyState'
import { Logo } from '@/components/ui/Logo'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Sheet } from '@/components/ui/Sheet'
import { Switch } from '@/components/ui/Switch'
import { PasswordField, TextField } from '@/components/ui/TextField'
import { LanguageToggle } from '@/features/auth/LanguageToggle'
import { TopicTile } from '@/features/onboarding/TopicTile'
import { ThemeToggle } from '@/features/profile/ThemeToggle'
import { articleFixture, industriesFixture } from '@/test/fixtures'

function Block({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="space-y-3">
      <h2 className="text-label-md tracking-wider text-outline uppercase">{title}</h2>
      {children}
    </section>
  )
}

const TYPE_SAMPLES = Object.keys(tokens.typeScale) as (keyof typeof tokens.typeScale)[]

/** Development-only gallery of every primitive and card in both themes and languages (route /dev/components). */
export function ComponentsPage() {
  const { t } = useTranslation()
  const [chip, setChip] = useState('all')
  const [toggle, setToggle] = useState(true)
  const [segment, setSegment] = useState('latest')
  const [sheetOpen, setSheetOpen] = useState(false)
  const [tiles, setTiles] = useState<Set<number>>(new Set([1]))
  const hero = articleFixture()
  const plain = articleFixture({
    id: 2,
    slug: 'plain',
    is_original: false,
    title: 'Wavelength AI raises €6M seed to optimise shipping routes',
    image: {
      thumb: 'https://picsum.photos/seed/ship/320/320',
      card: 'https://picsum.photos/seed/ship/800/500',
      hero: 'https://picsum.photos/seed/ship/1600/1000',
    },
    primary_industry: { slug: 'maritime', name: 'Maritime', color: '#00A884' },
    is_bookmarked: true,
  })
  const greek = articleFixture({
    id: 3,
    slug: 'greek',
    is_original: false,
    locale: 'el',
    title: 'Πιλοτικό αγροτεχνολογίας μειώνει 30% τη χρήση νερού στο Τρόοδος',
    primary_industry: { slug: 'agritech', name: 'Αγροτεχνολογία', color: '#10B981' },
  })

  return (
    <Screen>
      <main className="mx-auto w-full max-w-content space-y-space-xl px-margin py-space-lg lg:px-8">
        <div className="flex items-center justify-between">
          <Logo size="sm" />
          <h1 className="font-headline text-headline-md">{t('dev.title')}</h1>
        </div>
        <div className="grid gap-3">
          <ThemeToggle />
          <LanguageToggle />
        </div>

        <Block title="Typography">
          {TYPE_SAMPLES.map((name) => (
            <p
              key={name}
              className={`text-${name} ${name.startsWith('headline') ? 'font-headline' : name.startsWith('body') && name !== 'body-sm' ? 'font-body' : 'font-ui'}`}
            >
              {name} — Η καινοτομία στην Κύπρο · Innovation
            </p>
          ))}
        </Block>

        <Block title="Colours">
          <div className="grid grid-cols-4 gap-2 lg:grid-cols-8">
            {Object.keys(tokens.colors).map((name) => (
              <div key={name} className="space-y-1">
                <div
                  className="h-10 rounded-control border border-hairline"
                  style={{ backgroundColor: `var(--dc-${name})` }}
                />
                <p className="text-[9px] leading-tight break-all text-on-surface-variant">{name}</p>
              </div>
            ))}
          </div>
        </Block>

        <Block title="Buttons">
          <Button>{t('welcome.createAccount')}</Button>
          <Button variant="outline">{t('welcome.signIn')}</Button>
          <Button loading>{t('common.loading')}</Button>
          <Button variant="danger">{t('auth.signOut')}</Button>
          <Button variant="ghost">{t('welcome.guest')}</Button>
        </Block>

        <Block title="Badges & chips">
          <div className="flex flex-wrap gap-2">
            <Badge variant="original">Disrupt Original</Badge>
            <Badge variant="original-soft" dot>
              Disrupt Original
            </Badge>
            <Badge>Τεχνολογία & Startups</Badge>
            <Badge variant="cyan">Mountain Silicon</Badge>
            <Badge variant="neutral">Venture</Badge>
          </div>
          <div className="no-scrollbar flex gap-2 overflow-x-auto">
            {['all', ...industriesFixture.map((industry) => industry.slug)].map((slug) => (
              <Chip
                key={slug}
                selected={chip === slug}
                onClick={() => setChip(slug)}
                color={industriesFixture.find((industry) => industry.slug === slug)?.color}
              >
                {industriesFixture.find((industry) => industry.slug === slug)?.name ?? 'All'}
              </Chip>
            ))}
          </div>
        </Block>

        <Block title="Form controls">
          <TextField label={t('auth.email')} placeholder="you@example.com" />
          <TextField label={t('auth.name')} error={t('validation.required')} />
          <PasswordField label={t('auth.password')} hint={t('auth.passwordHint')} />
          <Checkbox label={t('auth.remember')} defaultChecked />
          <Switch
            checked={toggle}
            onChange={setToggle}
            label={t('onboarding.notifications.newsDaily')}
            hint={t('onboarding.notifications.newsDailyHint')}
          />
          <SegmentedControl
            label="Sub-tabs"
            value={segment}
            onChange={setSegment}
            options={[
              { value: 'latest', label: 'Latest' },
              { value: 'daily', label: 'Daily' },
              { value: 'monthly', label: 'Monthly' },
            ]}
          />
          <Button variant="outline" onClick={() => setSheetOpen(true)}>
            Open sheet
          </Button>
          <Sheet open={sheetOpen} onClose={() => setSheetOpen(false)} title={t('onboarding.industries.skipTitle')}>
            <p className="font-body text-body-md text-on-surface-variant">{t('onboarding.industries.skipBody')}</p>
          </Sheet>
        </Block>

        <Block title="Topic tiles">
          <div className="grid grid-cols-2 gap-3.5">
            {industriesFixture.slice(0, 4).map((industry) => (
              <TopicTile
                key={industry.id}
                industry={industry}
                showGroup
                selected={tiles.has(industry.id)}
                onToggle={(item) =>
                  setTiles((current) =>
                    current.has(item.id)
                      ? new Set([...current].filter((id) => id !== item.id))
                      : new Set([...current, item.id]),
                  )
                }
              />
            ))}
          </div>
        </Block>

        <Block title="Cards">
          <HeroCard article={hero} />
          <div className="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
            <StandardCard article={plain} />
            <StandardCard article={greek} />
          </div>
          <SectionHeader title="Trending Today" icon="trending_up" to="/" linkLabel="View rank" />
          <div className="-mx-margin no-scrollbar flex snap-x gap-3.5 overflow-x-auto px-margin pb-2">
            {[hero, plain, greek].map((article, index) => (
              <TrendingCard key={article.id} article={article} rank={index + 1} />
            ))}
          </div>
          <CompactCard article={plain} />
          <CompactCard article={greek} />
        </Block>

        <Block title="Skeletons">
          <HeroCardSkeleton />
          <StandardCardSkeleton />
          <CompactCardSkeleton />
        </Block>

        <Block title="States">
          <EmptyState
            icon="bookmark"
            title="Nothing saved yet"
            body="Tap the bookmark on any story to read it later, even offline."
            action={<Button>{t('errors.goHome')}</Button>}
          />
          <EmptyState icon="cloud_off" title={t('errors.network')} tone="error" />
        </Block>
      </main>
    </Screen>
  )
}
