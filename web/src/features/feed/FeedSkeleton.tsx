import { CompactCardSkeleton, HeroCardSkeleton, StandardCardSkeleton } from '@/components/content/CardSkeletons'

export function FeedSkeleton() {
  return (
    <div className="space-y-space-xl" role="status" aria-busy="true">
      <HeroCardSkeleton />
      <div className="grid gap-gutter sm:grid-cols-2">
        <StandardCardSkeleton />
        <StandardCardSkeleton />
      </div>
      <div className="space-y-space-sm">
        <CompactCardSkeleton />
        <CompactCardSkeleton />
      </div>
    </div>
  )
}
