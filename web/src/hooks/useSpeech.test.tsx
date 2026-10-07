import { act, renderHook } from '@testing-library/react'
import type { SpeechProvider } from '@/lib/tts'
import { useSpeech } from './useSpeech'

function fakeProvider(available: boolean): SpeechProvider & { spoken: string[] } {
  const spoken: string[] = []
  return {
    spoken,
    isAvailable: () => available,
    speak: (text, _locale, { onEnd }) => {
      spoken.push(text)
      onEnd?.()
    },
    pause: () => undefined,
    resume: () => undefined,
    stop: () => undefined,
  }
}

it('reports unavailable voices so the Listen button can hide', () => {
  const { result } = renderHook(() => useSpeech('el', fakeProvider(false)))

  expect(result.current.available).toBe(false)
})

it('plays through the provider and returns to idle at the end', () => {
  const provider = fakeProvider(true)
  const { result } = renderHook(() => useSpeech('en', provider))

  act(() => result.current.play('Hello Cyprus'))

  expect(provider.spoken).toEqual(['Hello Cyprus'])
  expect(result.current.state).toBe('idle')
  expect(result.current.progress).toBe(1)
})
