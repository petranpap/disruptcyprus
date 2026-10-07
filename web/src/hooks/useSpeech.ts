import { useEffect, useState } from 'react'
import type { Locale } from '@/api/schemas'
import { speechProvider, type SpeechProvider } from '@/lib/tts'

export type SpeechState = 'idle' | 'playing' | 'paused'

/** Reader "Listen": availability (voices load asynchronously), playback state and progress. */
export function useSpeech(locale: Locale, provider: SpeechProvider = speechProvider) {
  const [available, setAvailable] = useState(() => provider.isAvailable(locale))
  const [state, setState] = useState<SpeechState>('idle')
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    const update = () => setAvailable(provider.isAvailable(locale))
    update()
    if (typeof window === 'undefined' || !('speechSynthesis' in window)) return

    window.speechSynthesis.addEventListener?.('voiceschanged', update)
    return () => window.speechSynthesis.removeEventListener?.('voiceschanged', update)
  }, [locale, provider])

  useEffect(() => () => provider.stop(), [provider])

  return {
    available,
    state,
    progress,
    play: (text: string) => {
      setProgress(0)
      setState('playing')
      provider.speak(text, locale, {
        onProgress: setProgress,
        onEnd: () => {
          setState('idle')
          setProgress(1)
        },
      })
    },
    pause: () => {
      provider.pause()
      setState('paused')
    },
    resume: () => {
      provider.resume()
      setState('playing')
    },
    stop: () => {
      provider.stop()
      setState('idle')
    },
  }
}
