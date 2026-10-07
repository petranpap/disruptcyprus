import type { ArticleCard, Industry, NotificationPreferences, User } from '@/api/schemas'

/** Shared fixtures for unit tests, MSW handlers and the /dev/components gallery. */

export const userFixture: User = {
  id: 1,
  name: 'Anna Kyriakou',
  email: 'anna@example.com',
  email_verified: true,
  avatar_url: null,
  role: 'reader',
  locale: 'en',
  content_locales: ['el', 'en'],
  timezone: 'Asia/Nicosia',
  has_password: true,
  needs_consent: false,
  onboarded: true,
  created_at: '2026-10-01T08:00:00+00:00',
}

export const industriesFixture: Industry[] = [
  {
    id: 1,
    slug: 'fintech',
    name: 'FinTech',
    group: 'finance_investment',
    color: '#00A5E6',
    image_url: null,
    sort_order: 10,
  },
  {
    id: 2,
    slug: 'artificial-intelligence',
    name: 'Artificial Intelligence',
    group: 'deep_tech',
    color: '#7C4DFF',
    image_url: null,
    sort_order: 20,
  },
  { id: 3, slug: 'maritime', name: 'Maritime', group: 'sectors', color: '#00A884', image_url: null, sort_order: 30 },
  { id: 4, slug: 'cleantech', name: 'CleanTech', group: 'sectors', color: '#10B981', image_url: null, sort_order: 40 },
  { id: 5, slug: 'govtech', name: 'GovTech', group: 'society_gov', color: '#F59E0B', image_url: null, sort_order: 50 },
]

export const preferencesFixture: NotificationPreferences = {
  digest_news_daily: false,
  digest_news_monthly: false,
  digest_events_weekly: false,
  digest_events_monthly: false,
  event_reminders: true,
  delivery_time: '08:00',
}

const placeholderImage = (seed: string) => ({
  thumb: `https://picsum.photos/seed/${seed}/320/320`,
  card: `https://picsum.photos/seed/${seed}/800/500`,
  hero: `https://picsum.photos/seed/${seed}/1600/1000`,
})

export function articleFixture(overrides: Partial<ArticleCard> = {}): ArticleCard {
  return {
    type: 'article',
    id: 1,
    slug: 'cyprus-records-eur450m-in-tech-venture-inflows',
    title: 'Cyprus records €450M in tech venture inflows as the ecosystem matures',
    excerpt:
      'International funds and homegrown founders pushed venture investment to a new high in the first nine months of the year.',
    section: { slug: 'news', name: 'News' },
    primary_industry: { slug: 'funding-venture-capital', name: 'Funding & Venture Capital', color: '#8B5CF6' },
    industries: [
      { slug: 'funding-venture-capital', name: 'Funding & Venture Capital', color: '#8B5CF6', is_primary: true },
    ],
    author: { id: 1, name: 'Elena Vassiliou', is_verified: true },
    is_original: true,
    is_featured: true,
    published_at: new Date(Date.now() - 2 * 3600_000).toISOString(),
    reading_time_minutes: 4,
    reads: 12400,
    image: placeholderImage('hero'),
    locale: 'en',
    is_fallback: false,
    is_bookmarked: false,
    ...overrides,
  }
}
