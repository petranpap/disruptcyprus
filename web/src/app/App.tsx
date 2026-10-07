import { useState } from 'react'
import { RouterProvider } from 'react-router'
import { UpdatePrompt } from '@/components/layout/UpdatePrompt'
import { Providers } from './Providers'
import { createRouter } from './router'

export function App() {
  const [router] = useState(createRouter)

  return (
    <Providers>
      <RouterProvider router={router} />
      <UpdatePrompt />
    </Providers>
  )
}
