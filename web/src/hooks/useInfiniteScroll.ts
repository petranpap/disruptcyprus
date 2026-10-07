import { useEffect, useRef } from 'react'

/** Calls `onReach` when the sentinel scrolls into view (with a generous margin so loading starts early). */
export function useInfiniteScroll(onReach: () => void, enabled: boolean) {
  const sentinel = useRef<HTMLDivElement>(null)
  const callback = useRef(onReach)

  useEffect(() => {
    callback.current = onReach
  }, [onReach])

  useEffect(() => {
    const element = sentinel.current
    if (!element || !enabled || typeof IntersectionObserver === 'undefined') return

    const observer = new IntersectionObserver(
      (entries) => entries.some((entry) => entry.isIntersecting) && callback.current(),
      { rootMargin: '600px 0px' },
    )
    observer.observe(element)

    return () => observer.disconnect()
  }, [enabled])

  return sentinel
}
