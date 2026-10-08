import { act, screen, waitFor, within } from '@testing-library/react'
import { renderApp } from '@/test/render'
import { db } from '@/test/server'
import { userFixture } from '@/test/fixtures'

describe('route gates', () => {
  it('sends first-time guests to the Welcome screen', async () => {
    const { router } = renderApp('/')

    await waitFor(() => expect(router.state.location.pathname).toBe('/welcome'))
    expect(await screen.findByRole('heading', { name: /Cyprus startups and innovation/ })).toBeInTheDocument()
  })

  it('lets returning guests browse the app', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const { router } = renderApp('/')

    expect(await screen.findByRole('link', { name: 'Home' })).toBeInTheDocument()
    expect(router.state.location.pathname).toBe('/')
    expect(
      within(screen.getByRole('navigation', { name: 'Sections' })).getByRole('link', { name: 'Trending' }),
    ).toBeInTheDocument()
  })

  it('sends readers who have not finished onboarding back to it', async () => {
    db.user = { ...userFixture, onboarded: false }
    const { router } = renderApp('/')

    await waitFor(() => expect(router.state.location.pathname).toBe('/onboarding/account'))
  })

  it('keeps signed-in readers away from the auth screens', async () => {
    db.user = userFixture
    const { router } = renderApp('/sign-in')

    await waitFor(() => expect(router.state.location.pathname).toBe('/'))
  })
})

describe('welcome', () => {
  it('continues as guest', async () => {
    const { router, user } = renderApp('/welcome')

    await user.click(await screen.findByRole('button', { name: /Continue as guest/ }))

    expect(router.state.location.pathname).toBe('/')
    expect(window.localStorage.getItem('dc.welcomed')).toBe('1')
  })

  it('switches the UI language instantly', async () => {
    const { user } = renderApp('/welcome')

    await user.click(await screen.findByRole('radio', { name: 'ΕΛ' }))

    expect(await screen.findByRole('heading', { name: /Startups και καινοτομία/ })).toBeInTheDocument()
    expect(document.documentElement.lang).toBe('el')
    expect(window.localStorage.getItem('dc.locale')).toBe('el')
  })
})

describe('sign in', () => {
  it('validates fields before calling the API', async () => {
    const { user } = renderApp('/sign-in')

    await user.click(await screen.findByRole('button', { name: 'Sign in' }))

    expect(await screen.findByText('Enter a valid email address.')).toBeInTheDocument()
    expect(db.requests.some((request) => request.path.endsWith('/auth/login'))).toBe(false)
  })

  it('shows server errors on the matching field', async () => {
    const { user } = renderApp('/sign-in')

    await user.type(await screen.findByLabelText('Email'), 'anna@example.com')
    await user.type(screen.getByLabelText('Password'), 'wrong-password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(await screen.findByText('These credentials do not match our records.')).toBeInTheDocument()
    expect(screen.getByLabelText('Email')).toHaveAttribute('aria-invalid', 'true')
  })

  it('signs in and lands on the feed', async () => {
    const { router, user } = renderApp('/sign-in')

    await user.type(await screen.findByLabelText('Email'), 'anna@example.com')
    await user.type(screen.getByLabelText('Password'), 'password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    await waitFor(() => expect(router.state.location.pathname).toBe('/'))
    expect(
      within(await screen.findByRole('navigation', { name: 'Sections' })).getByRole('link', { name: 'For you' }),
    ).toBeInTheDocument()
  })

  it('explains a failed Google sign-in', async () => {
    renderApp('/sign-in?error=social_failed')

    expect(await screen.findByRole('alert')).toHaveTextContent('Google sign-in did not complete')
  })

  it('treats a cancelled Google sign-in gently', async () => {
    renderApp('/sign-in?error=social_cancelled')

    expect(await screen.findByRole('alert')).toHaveTextContent('Google sign-in was cancelled')
  })

  it('sends readers back to the story they wanted to save after Google sign-in', async () => {
    window.localStorage.setItem('dc.welcomed', '1')
    const { router } = renderApp('/')
    await act(() => router.navigate('/sign-in', { state: { from: '/articles/seed-round' } }))

    expect(await screen.findByRole('link', { name: 'Continue with Google' })).toHaveAttribute(
      'href',
      '/api/v1/auth/social/google/redirect?next=%2Farticles%2Fseed-round',
    )
  })
})

describe('sign up', () => {
  it('requires consent and a strong enough password', async () => {
    const { user } = renderApp('/sign-up')

    await user.type(await screen.findByLabelText('Name'), 'Anna')
    await user.type(screen.getByLabelText('Email'), 'anna@example.com')
    await user.type(screen.getByLabelText('Password'), 'short')
    await user.click(screen.getByRole('button', { name: 'Create an account' }))

    expect(await screen.findByText('Please accept the Terms and Privacy Policy.')).toBeInTheDocument()
    expect(screen.getByText('Use at least 8 characters, with a letter and a number.')).toBeInTheDocument()
  })

  it('creates the account with the UI language and timezone, then starts onboarding', async () => {
    const { router, user } = renderApp('/sign-up')

    await user.type(await screen.findByLabelText('Name'), 'Anna')
    await user.type(screen.getByLabelText('Email'), 'anna@example.com')
    await user.type(screen.getByLabelText('Password'), 'secret123')
    await user.click(screen.getByRole('checkbox', { name: /I agree/ }))
    await user.click(screen.getByRole('button', { name: 'Create an account' }))

    await waitFor(() => expect(router.state.location.pathname).toBe('/onboarding/account'))
    const register = db.requests.find((request) => request.path.endsWith('/auth/register'))
    expect(register?.body).toMatchObject({ name: 'Anna', email: 'anna@example.com', consent: true, locale: 'en' })
    expect((register?.body as { timezone: string }).timezone).toBeTruthy()
  })
})
