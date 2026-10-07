import { useThemeStore } from './theme'

describe('theme store', () => {
  it('applies and persists a manual theme, and clears it for system', () => {
    useThemeStore.getState().setPreference('dark')

    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(window.localStorage.getItem('dc.theme')).toBe('dark')

    useThemeStore.getState().setPreference('system')

    expect(document.documentElement.dataset.theme).toBe('light')
    expect(window.localStorage.getItem('dc.theme')).toBeNull()
  })
})
