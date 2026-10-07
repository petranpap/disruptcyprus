import { QueryClientProvider, type QueryClient } from '@tanstack/react-query'
import { useEffect, useState, type ReactNode } from 'react'
import { I18nextProvider } from 'react-i18next'
import { createQueryClient } from '@/api/queryClient'
import i18n from '@/i18n'
import { applyTheme, useThemeStore, watchSystemTheme } from '@/stores/theme'

export function Providers({ children, queryClient }: { children: ReactNode; queryClient?: QueryClient }) {
  const [client] = useState(() => queryClient ?? createQueryClient())

  useEffect(() => {
    applyTheme(useThemeStore.getState().resolved)

    return watchSystemTheme()
  }, [])

  return (
    <QueryClientProvider client={client}>
      <I18nextProvider i18n={i18n}>{children}</I18nextProvider>
    </QueryClientProvider>
  )
}
