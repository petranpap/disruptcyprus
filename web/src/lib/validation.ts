/** Mirrors the backend rule Password::min(8)->letters()->numbers(). */
export const PASSWORD_RULE = /^(?=.*\p{L})(?=.*\d).{8,}$/u
