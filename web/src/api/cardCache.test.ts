import { QueryClient } from '@tanstack/react-query'
import { patchCards } from './cardCache'

it('patches matching cards everywhere in the cache and leaves others untouched', () => {
  const client = new QueryClient()
  const untouched = { type: 'article', id: 2, is_bookmarked: false }
  client.setQueryData(['feed'], { pages: [{ data: [{ type: 'article', id: 1, is_bookmarked: false }, untouched] }] })
  client.setQueryData(['digest'], {
    items: [
      { item: { type: 'article', id: 1, is_bookmarked: false } },
      { item: { type: 'event', id: 1, is_bookmarked: false } },
    ],
  })

  patchCards(client, 'article', 1, { is_bookmarked: true })

  const feed = client.getQueryData<{ pages: { data: { id: number; is_bookmarked: boolean }[] }[] }>(['feed'])
  const digest = client.getQueryData<{ items: { item: { type: string; is_bookmarked: boolean } }[] }>(['digest'])
  expect(feed?.pages[0]?.data[0]?.is_bookmarked).toBe(true)
  expect(feed?.pages[0]?.data[1]).toBe(untouched)
  expect(digest?.items[0]?.item.is_bookmarked).toBe(true)
  expect(digest?.items[1]?.item.is_bookmarked).toBe(false)
})
