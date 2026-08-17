export const LOTDG_AUTOCOMPLETE_TOKEN = {
  USERNAME: 'username',
  CURRENT_PASSWORD: 'current-password',
  NEW_PASSWORD: 'new-password',
  EMAIL_ADDRESS: 'email',
} as const

export type LotdgAutocompleteToken =
  (typeof LOTDG_AUTOCOMPLETE_TOKEN)[keyof typeof LOTDG_AUTOCOMPLETE_TOKEN]

export const LOTDG_ACCESS_KEY = {
  LOGIN_NAME: 'u',
  PASSWORD: 'p',
} as const

export type LotdgAccessKey = (typeof LOTDG_ACCESS_KEY)[keyof typeof LOTDG_ACCESS_KEY]

export const LOTDG_BOOLEAN_FIELD_VALUE = {
  FALSE: '0',
  TRUE: '1',
} as const
