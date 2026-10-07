import { screen, waitFor, within } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'
import { userFixture } from '@/test/fixtures'

describe('home', () => {
  it('shows trending stories and a personalization prompt to guests', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    renderApp('/')

    expect(await screen.findByRole('heading', { name: /Cyprus records €450M/ })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Make it yours' })).toBeInTheDocument()
  })

  it('shows the personal feed and nudges readers without industries', async () => {
    db.user = userFixture
    renderApp('/')

    expect(await screen.findByRole('heading', { name: 'Lead story for you' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: "Here's what's trending" })).toBeInTheDocument()
    expect(screen.getAllByText('Pitch Night: Seed Edition').length).toBeGreaterThan(0)
  })

  it('saves a story optimistically and prompts guests to sign up instead', async () => {
    db.user = userFixture
    const { user } = renderApp('/')

    const card = (await screen.findByRole('heading', { name: 'Lead story for you' })).closest('article') as HTMLElement
    await user.click(within(card).getByRole('button', { name: 'Save' }))

    expect(within(card).getByRole('button', { name: 'Saved' })).toHaveAttribute('aria-pressed', 'true')
    await waitFor(() => expect(db.bookmarks.has('article:31')).toBe(true))
    expect(await screen.findByText(/Saved\. Available offline/)).toBeInTheDocument()
  })

  it('asks guests to create an account when they try to save', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const { user } = renderApp('/')

    const card = (await screen.findByRole('heading', { name: /Cyprus records €450M/ })).closest(
      'article',
    ) as HTMLElement
    await user.click(within(card).getByRole('button', { name: 'Save' }))

    expect(await screen.findByRole('dialog', { name: 'Save it for later' })).toBeInTheDocument()
    expect(db.requests.some((request) => request.path.endsWith('/bookmarks'))).toBe(false)
  })
})

describe('sections', () => {
  it('filters by industry chips and keeps the filter in the URL', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const { router, user } = renderApp('/startups')

    expect(await screen.findByRole('heading', { name: 'Lead in startups' })).toBeInTheDocument()
    await user.click(await screen.findByRole('button', { name: /FinTech/ }))

    await waitFor(() => expect(router.state.location.search).toBe('?industry=fintech'))
    await waitFor(() =>
      expect(
        db.requests.some(
          (request) =>
            request.path.endsWith('/sections/startups/articles') && request.search.includes('industry=fintech'),
        ),
      ).toBe(true),
    )
  })

  it('shows the latest Daily News digest with editor notes and "For you" highlights', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const { user } = renderApp('/news')

    await user.click(await screen.findByRole('radio', { name: 'Daily' }))

    expect(await screen.findByRole('heading', { name: 'Daily News — 7 October 2026' })).toBeInTheDocument()
    expect(screen.getByText('Our top pick.')).toBeInTheDocument()
    expect(screen.getByText('FOR YOU')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Daily News — 6 October 2026/ })).toBeInTheDocument()
  })
})
