import { screen } from '@testing-library/react'
import { renderApp } from '@/test/render'

beforeEach(() => window.localStorage.setItem('dc.welcomed', '1'))

it('shows the industries directory and searches as you type', async () => {
  const { user, router } = renderApp('/explore')

  expect(await screen.findByRole('link', { name: 'FinTech' })).toBeInTheDocument()
  await user.type(screen.getByRole('searchbox', { name: 'Search' }), 'seed')

  expect(await screen.findByRole('heading', { name: 'Result for seed' })).toBeInTheDocument()
  expect(router.state.location.search).toBe('?q=seed')
})

it('explains when nothing matches', async () => {
  const { user } = renderApp('/explore')

  await user.type(await screen.findByRole('searchbox', { name: 'Search' }), 'none')

  expect(await screen.findByText('Nothing matches “none”.')).toBeInTheDocument()
})
