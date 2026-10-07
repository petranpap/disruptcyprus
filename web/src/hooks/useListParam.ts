import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router'

/** A comma-separated list kept in the URL (e.g. ?industry=fintech,maritime) so filtered views are shareable. */
export function useListParam(name: string): [string[], (next: string[]) => void] {
  const [params, setParams] = useSearchParams()
  const raw = params.get(name) ?? ''
  const value = useMemo(() => (raw ? raw.split(',').filter(Boolean) : []), [raw])

  const setValue = useCallback(
    (next: string[]) =>
      setParams(
        (current) => {
          const updated = new URLSearchParams(current)
          if (next.length) updated.set(name, next.join(','))
          else updated.delete(name)
          return updated
        },
        { replace: true },
      ),
    [name, setParams],
  )

  return [value, setValue]
}

/** A single-value URL param with a default (e.g. ?tab=daily). */
export function useParam<T extends string>(name: string, fallback: T, allowed: readonly T[]): [T, (next: T) => void] {
  const [params, setParams] = useSearchParams()
  const raw = params.get(name)
  const value = (allowed as readonly string[]).includes(raw ?? '') ? (raw as T) : fallback

  const setValue = useCallback(
    (next: T) =>
      setParams(
        (current) => {
          const updated = new URLSearchParams(current)
          if (next === fallback) updated.delete(name)
          else updated.set(name, next)
          return updated
        },
        { replace: true },
      ),
    [fallback, name, setParams],
  )

  return [value, setValue]
}

/** A free-form URL param validated by a pattern (e.g. ?month=2026-10); invalid values fall back. */
export function usePatternParam(name: string, fallback: string, pattern: RegExp): [string, (next: string) => void] {
  const [params, setParams] = useSearchParams()
  const raw = params.get(name) ?? ''
  const value = pattern.test(raw) ? raw : fallback

  const setValue = useCallback(
    (next: string) =>
      setParams(
        (current) => {
          const updated = new URLSearchParams(current)
          updated.set(name, next)
          return updated
        },
        { replace: true },
      ),
    [name, setParams],
  )

  return [value, setValue]
}
