import { screen, waitFor, within } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'
import { userFixture } from '@/test/fixtures'

beforeEach(() => {
  db.user = userFixture
})

it('turns on alerts for a followed industry', async () => {
  db.myIndustries = { industry_ids: [1, 3], notify_ids: [] }
  const { user } = renderApp('/settings/industries')

  await user.click(await screen.findByRole('switch', { name: 'FinTech' }))
  await user.click(screen.getByRole('button', { name: 'Save' }))

  await waitFor(() => expect(db.myIndustries).toEqual({ industry_ids: [1, 3], notify_ids: [1] }))
})

it('saves notification preferences', async () => {
  const { user } = renderApp('/settings/notifications')

  await user.click(await screen.findByRole('switch', { name: 'Monthly Events' }))
  await user.click(screen.getByRole('button', { name: 'Save' }))

  await waitFor(() => expect(db.preferences.digest_events_monthly).toBe(true))
})

it('validates and reports password changes', async () => {
  const { user } = renderApp('/settings/account')

  const card = (await screen.findByRole('heading', { name: 'Change password' })).closest('section') as HTMLElement
  await user.type(within(card).getByLabelText('Current password'), 'wrong-one')
  await user.type(within(card).getByLabelText('New password'), 'newpass123')
  await user.type(within(card).getByLabelText('Confirm password'), 'newpass123')
  await user.click(within(card).getByRole('button', { name: 'Change password' }))

  expect(await within(card).findByText('The password is incorrect.')).toBeInTheDocument()
})

it('requests a data export and deletes the account after confirmation', async () => {
  const { user, router } = renderApp('/settings/privacy')

  await user.click(await screen.findByRole('button', { name: 'Request data export' }))
  expect(await screen.findByText(/preparing your export/)).toBeInTheDocument()

  await user.click(screen.getByRole('button', { name: 'Delete my account' }))
  const dialog = await screen.findByRole('dialog', { name: 'Delete your account?' })
  await user.type(within(dialog).getByLabelText('Enter your password to confirm'), 'wrong')
  await user.click(within(dialog).getByRole('button', { name: 'Permanently delete' }))
  expect(await within(dialog).findByText('The password is incorrect.')).toBeInTheDocument()

  await user.clear(within(dialog).getByLabelText('Enter your password to confirm'))
  await user.type(within(dialog).getByLabelText('Enter your password to confirm'), 'password')
  await user.click(within(dialog).getByRole('button', { name: 'Permanently delete' }))

  await waitFor(() => expect(router.state.location.pathname).toBe('/welcome'))
  expect(db.user).toBeNull()
})

it('keeps account settings behind sign-in', async () => {
  db.user = null
  const { router } = renderApp('/settings/account')

  await waitFor(() => expect(router.state.location.pathname).toBe('/sign-in'))
})
