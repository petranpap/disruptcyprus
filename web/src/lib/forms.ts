import type { FieldValues, Path, UseFormSetError } from 'react-hook-form'
import { ApiError, NetworkError } from '@/api/errors'
import i18n from '@/i18n'

/**
 * Puts server validation messages (already in the UI language) on the matching fields.
 * Returns a general message for errors that do not belong to a field.
 */
export function applyServerErrors<T extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<T>,
  fields: Path<T>[],
): string | null {
  if (error instanceof ApiError && error.isValidation) {
    let unmatched: string | null = null

    for (const [field, message] of Object.entries(error.fieldErrors())) {
      if ((fields as string[]).includes(field)) {
        setError(field as Path<T>, { type: 'server', message })
      } else {
        unmatched ??= message
      }
    }

    return unmatched
  }

  if (error instanceof ApiError) return error.message
  if (error instanceof NetworkError) return i18n.t('errors.network')

  return i18n.t('errors.generic')
}
