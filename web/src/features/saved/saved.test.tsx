import { screen } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'
import { userFixture } from '@/test/fixtures'

it('lists saved stories for readers', async () => {
  db.user = userFixture
  db.bookmarks.add('article:5')
  renderApp('/saved')

  expect(await screen.findByRole('heading', { name: 'Saved story' })).toBeInTheDocument()
})

it('shows an empty state with nothing saved', async () => {
  db.user = userFixture
  renderApp('/saved')

  expect(await screen.findByText('Nothing saved yet')).toBeInTheDocument()
})

it('invites guests to create an account', async () => {
  window.localStorage.setItem('dc.welcomed', '1')
  renderApp('/saved')

  expect(await screen.findByText('Save stories for later')).toBeInTheDocument()
})
