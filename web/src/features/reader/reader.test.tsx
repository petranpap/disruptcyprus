import { screen, waitFor } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'

beforeEach(() => window.localStorage.setItem('dc.welcomed', '1'))

it('renders the article with byline, body and related stories, and records the read', async () => {
  renderApp('/articles/cyprus-records')

  expect(await screen.findByRole('heading', { level: 1, name: /Cyprus records €450M/ })).toBeInTheDocument()
  expect(screen.getByText('Elena Vassiliou')).toBeInTheDocument()
  expect(screen.getByRole('img', { name: 'Verified author' })).toBeInTheDocument()
  expect(screen.getByText('A quote.')).toBeInTheDocument()
  expect(screen.getByText('Limassol Marina')).toBeInTheDocument()
  expect(await screen.findByRole('heading', { name: 'Related story' })).toBeInTheDocument()
  await waitFor(() =>
    expect(db.requests.some((request) => request.method === 'POST' && request.path.endsWith('/articles/1/view'))).toBe(
      true,
    ),
  )
})

it('cycles the text size and remembers it', async () => {
  const { user } = renderApp('/articles/cyprus-records')

  const button = await screen.findByRole('button', { name: 'Text size 1 of 3' })
  await user.click(button)

  expect(screen.getByRole('button', { name: 'Text size 2 of 3' })).toBeInTheDocument()
  expect(window.localStorage.getItem('dc.reader-font-size')).toBe('1')
})

it('hides Listen when the device has no speech voices', async () => {
  renderApp('/articles/cyprus-records')

  await screen.findByRole('heading', { level: 1, name: /Cyprus records €450M/ })
  expect(screen.queryByRole('button', { name: /Listen/ })).not.toBeInTheDocument()
})
