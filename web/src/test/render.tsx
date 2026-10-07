import { render } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { createQueryClient } from '@/api/queryClient'
import { Providers } from '@/app/Providers'
import { routes } from '@/app/router'

/** Renders the real route tree at `path` with fresh providers. Returns the router for location assertions. */
export function renderApp(path: string) {
  const queryClient = createQueryClient()
  queryClient.setDefaultOptions({ queries: { retry: false, staleTime: 0 }, mutations: { retry: false } })
  const router = createMemoryRouter(routes, { initialEntries: [path] })
  const user = userEvent.setup()

  render(
    <Providers queryClient={queryClient}>
      <RouterProvider router={router} />
    </Providers>,
  )

  return { router, user, queryClient }
}
