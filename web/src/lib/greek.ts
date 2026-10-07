const GREEK = /[Ͱ-Ͽἀ-῿]/

/**
 * Uppercase for labels and badges. Greek capitals drop their accents (ΤΕΧΝΟΛΟΓΙΑ, not ΤΕΧΝΟΛΟΓΊΑ);
 * browsers do not handle this consistently with `text-transform`, so labels are uppercased in code.
 */
export function upper(text: string): string {
  if (!GREEK.test(text)) {
    return text.toLocaleUpperCase('en')
  }

  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleUpperCase('el').normalize('NFC')
}

/** Lowercase without accents, for accent-insensitive filtering ("ναυτιλια" matches "Ναυτιλία"). */
export function fold(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase()
}
