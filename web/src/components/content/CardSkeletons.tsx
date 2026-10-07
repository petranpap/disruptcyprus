import { Skeleton } from '@/components/ui/Skeleton'

/** Skeletons match each card's geometry so content does not jump when it loads. */
export function HeroCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-card bg-surface-container-lowest">
      <Skeleton className="aspect-[4/3] w-full rounded-none sm:aspect-[16/9]" />
      <div className="space-y-2 p-space-md">
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-2/3" />
      </div>
    </div>
  )
}

export function StandardCardSkeleton() {
  return (
    <div className="space-y-3 rounded-card border border-card-stroke bg-surface-container-lowest p-3.5">
      <Skeleton className="aspect-[16/10] w-full" />
      <Skeleton className="h-5 w-full" />
      <Skeleton className="h-5 w-3/4" />
      <Skeleton className="h-3 w-1/3" />
    </div>
  )
}

export function CompactCardSkeleton() {
  return (
    <div className="flex items-center gap-3.5 rounded-card border border-card-stroke bg-surface-container-lowest p-3">
      <Skeleton className="size-20 shrink-0" />
      <div className="flex-1 space-y-2">
        <Skeleton className="h-3 w-1/3" />
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-2/3" />
      </div>
    </div>
  )
}
