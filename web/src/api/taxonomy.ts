import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { z } from 'zod'
import { api } from './client'
import { dataSchema, industrySchema, myIndustriesSchema, sectionSchema, type MyIndustries } from './schemas'

const TAXONOMY_STALE = 60 * 60_000

export function useIndustries(locale: string) {
  return useQuery({
    queryKey: ['industries', locale],
    queryFn: async () => (await api.get('/industries', { schema: dataSchema(z.array(industrySchema)) })).data,
    staleTime: TAXONOMY_STALE,
  })
}

export function useSections(locale: string) {
  return useQuery({
    queryKey: ['sections', locale],
    queryFn: async () => (await api.get('/sections', { schema: dataSchema(z.array(sectionSchema)) })).data,
    staleTime: TAXONOMY_STALE,
  })
}

export const myIndustriesKey = ['me', 'industries'] as const

export function useMyIndustries(enabled = true) {
  return useQuery({
    queryKey: myIndustriesKey,
    queryFn: async () => (await api.get('/me/industries', { schema: dataSchema(myIndustriesSchema) })).data,
    enabled,
  })
}

export function useUpdateMyIndustries() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: MyIndustries) =>
      (await api.put('/me/industries', input, { schema: dataSchema(myIndustriesSchema) })).data,
    onSuccess: (data) => {
      queryClient.setQueryData(myIndustriesKey, data)
      // The personalized feed depends on followed industries.
      void queryClient.invalidateQueries({ queryKey: ['feed'] })
    },
  })
}
