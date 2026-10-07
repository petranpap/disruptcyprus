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

import type { Article, Calendar, Digest, EventCard, Event as EventDetail } from '@/api/schemas'

export function eventFixture(overrides: Partial<EventCard> = {}): EventCard {
  return {
    type: 'event',
    id: 10,
    slug: 'pitch-night',
    title: 'Pitch Night: Seed Edition',
    excerpt: 'Eight startups pitch to angels and funds.',
    starts_at: '2026-10-08T15:00:00+00:00',
    ends_at: '2026-10-08T18:00:00+00:00',
    timezone: 'Asia/Nicosia',
    is_online: false,
    city: 'Larnaca',
    location_name: 'Port Tech Quarter',
    price_info: 'Free',
    primary_industry: { slug: 'funding-venture-capital', name: 'Funding & Venture Capital', color: '#8B5CF6' },
    industries: [
      { slug: 'funding-venture-capital', name: 'Funding & Venture Capital', color: '#8B5CF6', is_primary: true },
    ],
    is_featured: true,
    image: null,
    locale: 'en',
    is_fallback: false,
    is_bookmarked: false,
    ...overrides,
  }
}

export function eventDetailFixture(overrides: Partial<EventDetail> = {}): EventDetail {
  return {
    ...eventFixture(),
    description: '<p>Eight startups pitch.</p>',
    address: 'Port Tech Quarter, Larnaca',
    online_url: null,
    registration_url: 'https://example.com/register',
    organizer_name: 'Disrupt Cyprus Community',
    available_locales: ['el', 'en'],
    ics_url: 'http://localhost:3000/api/v1/events/pitch-night/ics',
    share_url: 'http://localhost:8080/e/pitch-night',
    ...overrides,
  }
}

export function articleDetailFixture(overrides: Partial<Article> = {}): Article {
  return {
    ...articleFixture(),
    body: '<p>First paragraph with a <a href="https://example.com" rel="noopener noreferrer nofollow">link</a>.</p><blockquote><p>A quote.</p><cite>Someone</cite></blockquote>',
    hero_caption: 'Limassol Marina',
    author: {
      id: 1,
      name: 'Elena Vassiliou',
      title: 'Lead Tech Editor',
      bio: null,
      avatar_url: null,
      is_verified: true,
    },
    attachment: null,
    available_locales: ['el', 'en'],
    share_url: 'http://localhost:8080/a/cyprus-records-eur450m-in-tech-venture-inflows',
    ...overrides,
  }
}

export function digestFixture(overrides: Partial<Digest> = {}): Digest {
  return {
    id: 1,
    slug: 'daily-news-2026-10-07',
    kind: 'news',
    cadence: 'daily',
    period_start: '2026-10-07',
    period_end: '2026-10-07',
    title: 'Daily News — 7 October 2026',
    intro: 'The stories our editors picked for you.',
    published_at: '2026-10-07T06:30:00+00:00',
    items_count: 2,
    share_url: 'http://localhost:8080/d/daily-news-2026-10-07',
    items: [
      {
        id: 1,
        position: 1,
        editor_note: 'Our top pick.',
        is_highlighted: false,
        item: articleFixture({ id: 21, slug: 'top-pick', title: 'Top pick story' }),
      },
      {
        id: 2,
        position: 2,
        editor_note: null,
        is_highlighted: true,
        item: articleFixture({ id: 22, slug: 'for-you-story', title: 'Story in your industry', is_original: false }),
      },
    ],
    ...overrides,
  }
}

export const calendarFixture: Calendar = {
  month: '2026-10',
  timezone: 'Asia/Nicosia',
  days: [
    { date: '2026-10-08', events: [eventFixture()] },
    { date: '2026-10-19', events: [eventFixture({ id: 11, slug: 'hackathon', title: 'Smart Campus Hackathon' })] },
  ],
}
