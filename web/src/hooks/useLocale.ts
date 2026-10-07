import { useQueryClient } from '@tanstack/react-query'
import { useCallback } from 'react'
import { useTranslation } from 'react-i18next'
import { useMe, useUpdateProfile } from '@/api/auth'
import type { Locale } from '@/api/schemas'
import { STORAGE_KEYS, storage } from '@/lib/storage'

/**
 * UI language: switches instantly, persists on this device, syncs to the account when signed in,
 * and refetches content (Accept-Language changes the response).
 */
export function useLocale() {
  const { i18n } = useTranslation()
  const queryClient = useQueryClient()
  const { data: user } = useMe()
  const updateProfile = useUpdateProfile()
  const locale: Locale = i18n.language === 'en' ? 'en' : 'el'

  const setLocale = useCallback(
    async (next: Locale, options: { sync?: boolean } = {}) => {
      storage.set(STORAGE_KEYS.locale, next)
      await i18n.changeLanguage(next)
      void queryClient.invalidateQueries({ predicate: (query) => query.queryKey[0] !== 'me' })

      if ((options.sync ?? true) && user && user.locale !== next) {
        updateProfile.mutate({ locale: next })
      }
    },
    [i18n, queryClient, updateProfile, user],
  )

  return { locale, setLocale }
}
