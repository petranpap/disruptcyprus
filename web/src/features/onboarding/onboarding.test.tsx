import { screen, waitFor, within } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'
import { userFixture } from '@/test/fixtures'

beforeEach(() => {
  db.user = { ...userFixture, onboarded: false }
})

it('saves account preferences and asks Google users for consent', async () => {
  db.user = { ...userFixture, onboarded: false, needs_consent: true }
  const { router, user } = renderApp('/onboarding/account')

  await user.click(await screen.findByRole('button', { name: /Continue/ }))
  expect(await screen.findByText('Please accept the Terms and Privacy Policy.')).toBeInTheDocument()

  await user.click(screen.getByRole('checkbox', { name: /English/ }))
  await user.click(screen.getByRole('checkbox', { name: /I agree/ }))
  await user.click(screen.getByRole('button', { name: /Continue/ }))

  await waitFor(() => expect(router.state.location.pathname).toBe('/onboarding/industries'))
  expect(db.requests.find((request) => request.method === 'PATCH')?.body).toMatchObject({
    content_locales: ['el'],
    consent: true,
    locale: 'en',
  })
})

it('requires at least three industries before continuing', async () => {
  const { router, user } = renderApp('/onboarding/industries')

  const continueButton = await screen.findByRole('button', { name: 'Select 3 more' })
  expect(continueButton).toBeDisabled()

  await user.click(await screen.findByRole('button', { name: /FinTech/ }))
  await user.click(screen.getByRole('button', { name: /Maritime/ }))
  expect(screen.getByRole('button', { name: 'Select 1 more' })).toBeDisabled()

  await user.click(screen.getByRole('button', { name: /GovTech/ }))
  expect(screen.getByRole('button', { name: /FinTech/ })).toHaveAttribute('aria-pressed', 'true')
  expect(screen.getByText('3 of 5 chosen')).toBeInTheDocument()

  await user.click(screen.getByRole('button', { name: 'Continue (3 selected)' }))

  await waitFor(() => expect(router.state.location.pathname).toBe('/onboarding/notifications'))
  expect(db.myIndustries.industry_ids.sort()).toEqual([1, 3, 5])
})

it('filters industries without accents', async () => {
  const { user } = renderApp('/onboarding/industries')

  await user.type(await screen.findByPlaceholderText('Filter industries'), 'mari')

  expect(screen.getByRole('button', { name: /Maritime/ })).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: /FinTech/ })).not.toBeInTheDocument()
})

it('confirms before skipping personalization', async () => {
  const { router, user } = renderApp('/onboarding/industries')

  await user.click(await screen.findByRole('button', { name: 'Skip' }))
  const sheet = await screen.findByRole('dialog')
  await user.click(within(sheet).getByRole('button', { name: 'Skip for now' }))

  expect(router.state.location.pathname).toBe('/onboarding/notifications')
})

it('suggests two digests, saves preferences and completes onboarding', async () => {
  const { router, user } = renderApp('/onboarding/notifications')

  const daily = await screen.findByRole('switch', { name: 'Daily News' })
  expect(daily).toHaveAttribute('aria-checked', 'true')
  expect(screen.getByRole('switch', { name: 'Monthly News' })).toHaveAttribute('aria-checked', 'false')

  await user.click(screen.getByRole('switch', { name: 'Monthly News' }))
  await user.selectOptions(screen.getByRole('combobox', { name: 'Delivery time' }), '07:00')
  await user.click(screen.getByRole('button', { name: /Finish/ }))

  await waitFor(() => expect(router.state.location.pathname).toBe('/'))
  expect(db.preferences).toMatchObject({
    digest_news_daily: true,
    digest_events_weekly: true,
    digest_news_monthly: true,
    delivery_time: '07:00',
  })
  expect(db.user?.onboarded).toBe(true)
})

it('offers a retry when industries fail to load', async () => {
  const { http, HttpResponse } = await import('msw')
  const { server } = await import('@/test/server')
  server.use(
    http.get('*/api/v1/industries', () =>
      HttpResponse.json({ message: 'Server error.', code: 'server_error' }, { status: 500 }),
    ),
  )

  renderApp('/onboarding/industries')

  expect(await screen.findByRole('button', { name: 'Try again' })).toBeInTheDocument()
  expect(screen.queryByText(/No industry matches/)).not.toBeInTheDocument()
})
