import { screen, within } from '@testing-library/react'
import { renderApp } from '@/test/render'

beforeEach(() => window.localStorage.setItem('dc.welcomed', '1'))

it('lists upcoming events grouped by day', async () => {
  renderApp('/events')

  expect(await screen.findByRole('heading', { name: 'Pitch Night: Seed Edition' })).toBeInTheDocument()
})

it('shows the weekly digest above "This week"', async () => {
  const { user } = renderApp('/events')

  await user.click(await screen.findByRole('radio', { name: 'This week' }))

  expect(await screen.findByText('Events this week — 5–11 October')).toBeInTheDocument()
})

it('selects a day in the month calendar', async () => {
  const { user } = renderApp('/events?range=month&view=calendar&month=2026-10')

  const calendar = await screen.findByRole('region', { name: 'Calendar' })
  await user.click(await within(calendar).findByRole('button', { name: /19 October, 1 event/ }))

  expect(await screen.findByRole('heading', { name: 'Smart Campus Hackathon' })).toBeInTheDocument()
  expect(screen.queryByRole('heading', { name: 'Pitch Night: Seed Edition' })).not.toBeInTheDocument()
})

it('shows event details with registration and calendar file', async () => {
  renderApp('/events/pitch-night')

  expect(await screen.findByRole('heading', { level: 1, name: 'Pitch Night: Seed Edition' })).toBeInTheDocument()
  expect(screen.getAllByRole('link', { name: /Register/ })[0]).toHaveAttribute('href', 'https://example.com/register')
  expect(screen.getAllByRole('link', { name: /Add to calendar/ })[0]).toHaveAttribute(
    'href',
    expect.stringContaining('/events/pitch-night/ics'),
  )
})
