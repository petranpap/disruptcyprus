import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { disablePush } from '@/lib/push'
import { api } from './client'
import { ApiError } from './errors'
import { dataSchema, userSchema, type Locale, type User } from './schemas'

export const meQueryKey = ['me'] as const

/** The signed-in reader, or null for guests. */
export function useMe() {
  return useQuery({
    queryKey: meQueryKey,
    queryFn: async (): Promise<User | null> => {
      try {
        return (await api.get('/me', { schema: dataSchema(userSchema) })).data
      } catch (error) {
        if (error instanceof ApiError && error.isUnauthenticated) return null
        throw error
      }
    },
    staleTime: 5 * 60_000,
  })
}

export interface SignInInput {
  email: string
  password: string
  remember: boolean
}

export interface SignUpInput {
  name: string
  email: string
  password: string
  consent: boolean
  locale: Locale
  timezone: string
}

export interface ResetPasswordInput {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface ProfileUpdate {
  name?: string
  locale?: Locale
  content_locales?: Locale[]
  timezone?: string
  consent?: true
  onboarding_completed?: boolean
}

function useSetUser() {
  const queryClient = useQueryClient()

  return (user: User | null) => queryClient.setQueryData(meQueryKey, user)
}

export function useSignIn() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async (input: SignInInput) =>
      (await api.post('/auth/login', input, { schema: dataSchema(userSchema) })).data,
    onSuccess: setUser,
  })
}

export function useSignUp() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async (input: SignUpInput) =>
      (await api.post('/auth/register', input, { schema: dataSchema(userSchema) })).data,
    onSuccess: setUser,
  })
}

export function useSignOut() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async () => {
      // Shared devices must not keep receiving this reader's notifications.
      await disablePush().catch(() => undefined)
      await api.post<undefined>('/auth/logout')
    },
    onSettled: () => {
      queryClient.clear()
      queryClient.setQueryData(meQueryKey, null)
    },
  })
}

export function useForgotPassword() {
  return useMutation({
    mutationFn: (email: string) => api.post<{ message: string }>('/auth/forgot-password', { email }),
  })
}

export function useResetPassword() {
  return useMutation({
    mutationFn: (input: ResetPasswordInput) => api.post<{ message: string }>('/auth/reset-password', input),
  })
}

export function useUpdateProfile() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async (update: ProfileUpdate) =>
      (await api.patch('/me', update, { schema: dataSchema(userSchema) })).data,
    onSuccess: setUser,
  })
}

export function useUploadAvatar() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async (file: File) => {
      const form = new FormData()
      form.append('avatar', file)
      return (await api.post('/me/avatar', form, { schema: dataSchema(userSchema) })).data
    },
    onSuccess: setUser,
  })
}

export function useRemoveAvatar() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async () => (await api.delete('/me/avatar', undefined, { schema: dataSchema(userSchema) })).data,
    onSuccess: setUser,
  })
}

export interface PasswordChange {
  current_password?: string
  password: string
  password_confirmation: string
}

export function useChangePassword() {
  return useMutation({ mutationFn: (input: PasswordChange) => api.put<{ message: string }>('/me/password', input) })
}

export function useUpdateEmail() {
  const setUser = useSetUser()

  return useMutation({
    mutationFn: async (input: { email: string; current_password?: string }) =>
      (await api.patch('/me', input, { schema: dataSchema(userSchema) })).data,
    onSuccess: setUser,
  })
}

export function useRequestDataExport() {
  return useMutation({ mutationFn: () => api.post<{ message: string }>('/me/export') })
}

export function useDeleteAccount() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: { password?: string; confirmation?: string }) => api.delete<undefined>('/me', input),
    onSuccess: () => {
      queryClient.clear()
      queryClient.setQueryData(meQueryKey, null)
    },
  })
}
