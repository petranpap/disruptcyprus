import { useState } from 'react'
import { STORAGE_KEYS, storage } from '@/lib/storage'

/** Reader "A+": three body sizes, remembered on this device. */
export const FONT_SIZES = [
  'text-body-lg leading-[1.75]',
  'text-[20px] leading-[1.85]',
  'text-[22px] leading-[1.9]',
] as const

export function useReaderFontSize() {
  const [step, setStep] = useState(() => {
    const stored = Number(storage.get(STORAGE_KEYS.readerFontSize))
    return Number.isInteger(stored) && stored >= 0 && stored < FONT_SIZES.length ? stored : 0
  })

  return {
    step,
    className: FONT_SIZES[step] ?? FONT_SIZES[0],
    cycle: () =>
      setStep((current) => {
        const next = (current + 1) % FONT_SIZES.length
        storage.set(STORAGE_KEYS.readerFontSize, String(next))
        return next
      }),
  }
}
