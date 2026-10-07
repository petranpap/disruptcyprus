import { useMe } from '@/api/auth'
import { useMyIndustries, useUpdateMyIndustries } from '@/api/taxonomy'
import { useUiStore } from '@/stores/ui'

/** Follow/unfollow one industry (keeps its alert setting); guests get the sign-up prompt. */
export function useFollowIndustry(industryId: number | undefined) {
  const { data: user } = useMe()
  const mine = useMyIndustries(Boolean(user))
  const update = useUpdateMyIndustries()
  const openGuestGate = useUiStore((state) => state.openGuestGate)
  const following = industryId !== undefined && (mine.data?.industry_ids.includes(industryId) ?? false)

  const toggle = () => {
    if (!user) {
      openGuestGate('follow')
      return
    }
    if (industryId === undefined || !mine.data) return

    const industryIds = following
      ? mine.data.industry_ids.filter((id) => id !== industryId)
      : [...mine.data.industry_ids, industryId]
    update.mutate({
      industry_ids: industryIds,
      notify_ids: mine.data.notify_ids.filter((id) => industryIds.includes(id)),
    })
  }

  return { following, toggle, pending: update.isPending }
}
