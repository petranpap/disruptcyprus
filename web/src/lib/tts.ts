import type { Locale } from '@/api/schemas'

/**
 * Text-to-speech behind a small interface so a server-side TTS service can replace the browser engine later.
 * Quality limits of the Web Speech API: voices depend on the device (Greek is reliable on Android Chrome,
 * iOS/macOS Safari and Windows Edge; often missing on Linux desktops); no control over pronunciation.
 */
export interface SpeechProvider {
  isAvailable(locale: Locale): boolean
  speak(text: string, locale: Locale, callbacks: { onProgress?: (ratio: number) => void; onEnd?: () => void }): void
  pause(): void
  resume(): void
  stop(): void
}

const LANG_TAGS: Record<Locale, string> = { el: 'el-GR', en: 'en-GB' }

export class WebSpeechProvider implements SpeechProvider {
  private get synth(): SpeechSynthesis | null {
    return typeof window !== 'undefined' && 'speechSynthesis' in window ? window.speechSynthesis : null
  }

  private voiceFor(locale: Locale): SpeechSynthesisVoice | undefined {
    const voices = this.synth?.getVoices() ?? []

    return (
      voices.find((voice) => voice.lang === LANG_TAGS[locale]) ??
      voices.find((voice) => voice.lang.toLowerCase().startsWith(locale))
    )
  }

  isAvailable(locale: Locale): boolean {
    return Boolean(this.synth && this.voiceFor(locale))
  }

  speak(
    text: string,
    locale: Locale,
    { onProgress, onEnd }: { onProgress?: (ratio: number) => void; onEnd?: () => void },
  ): void {
    const synth = this.synth
    if (!synth) return

    synth.cancel()
    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = LANG_TAGS[locale]
    const voice = this.voiceFor(locale)
    if (voice) utterance.voice = voice
    utterance.onboundary = (event) => onProgress?.(Math.min(1, event.charIndex / Math.max(1, text.length)))
    utterance.onend = () => onEnd?.()
    synth.speak(utterance)
  }

  pause(): void {
    this.synth?.pause()
  }

  resume(): void {
    this.synth?.resume()
  }

  stop(): void {
    this.synth?.cancel()
  }
}

export const speechProvider: SpeechProvider = new WebSpeechProvider()

/** Plain text for reading aloud from sanitized article HTML. */
export function htmlToSpeechText(html: string): string {
  const element = document.createElement('div')
  element.innerHTML = html

  return (element.textContent ?? '').replace(/\s+/g, ' ').trim()
}
