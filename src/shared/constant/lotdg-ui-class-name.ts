import type { LotdgStageSceneCode } from '../../app/layout/lotdg-stage-scene-code'

export const LOTDG_UI_CLASS_NAME = {
  SHELL_ROOT: 'lotdg-shell',
  SHELL_HEADER: 'lotdg-shell__header',
  SHELL_TITLE_BANNER: 'lotdg-shell__title-banner',
  SHELL_PAGE_TITLE: 'lotdg-shell__page-title',
  SHELL_HEADER_LINK: 'lotdg-shell__header-link',
  SHELL_RAIL: 'lotdg-shell__rail',
  SHELL_STAGE: 'lotdg-shell__stage',
  SHELL_STAT: 'lotdg-shell__stat',
  SHELL_FOOTER: 'lotdg-shell__footer',

  PANEL_ROOT: 'lotdg-panel',
  PANEL_CAP: 'lotdg-panel__cap',
  PANEL_BODY: 'lotdg-panel__body',

  NAVIGATION_HEAD: 'lotdg-navigation__head',
  NAVIGATION_ITEM: 'lotdg-navigation__item',
  NAVIGATION_HELP: 'lotdg-navigation__help',

  STAT_TABLE: 'lotdg-stat',
  STAT_TABLE_WIDE: 'lotdg-stat lotdg-stat--wide',
  STAT_HEAD: 'lotdg-stat__head',
  STAT_LABEL: 'lotdg-stat__label',
  STAT_VALUE: 'lotdg-stat__value',
  STAT_BUFF: 'lotdg-stat__buff',
  STAT_BUFF_ROW: 'lotdg-stat__buff-row',
  STAT_ROSTER_HEAD: 'lotdg-stat__roster-head',
  STAT_ROSTER_NAME: 'lotdg-stat__roster-name',
  STAT_ROSTER_EMPTY: 'lotdg-stat__roster-empty',

  TABLE_SCROLL: 'lotdg-table-scroll',
  TABLE_ROOT: 'lotdg-table',

  INPUT: 'lotdg-input',
  TEXTAREA: 'lotdg-textarea',
  SELECT: 'lotdg-select',
  CHECKBOX: 'lotdg-checkbox',
  BUTTON: 'lotdg-button',
  FIELD_ROW: 'lotdg-field__row',
  FIELD_ROW_STACKED: 'lotdg-field__row lotdg-field__row--stacked',
  FIELD_LABEL: 'lotdg-field__label',
  ACTION_ROW: 'lotdg-action-row',
  ACTION_ROW_CENTERED: 'lotdg-action-row lotdg-action-row--centered',
  PAGINATION: 'lotdg-pagination',

  NOTICE_LINE: 'lotdg-notice-line',
  NOTICE_LINK: 'lotdg-notice-link',

  SCREEN_ROOT: 'lotdg-screen',
  SCREEN_TITLE: 'lotdg-screen__title',
  SECTION_ROOT: 'lotdg-section',
  SECTION_TITLE: 'lotdg-section__title',
  SECTION_BODY: 'lotdg-section__body',
  TEXT: 'lotdg-text',
  ALIGN_CENTER: 'lotdg-align-center',

  LOGIN_PANEL: 'lotdg-login-panel',
  LOGIN_PANEL_FIELD: 'lotdg-login-panel__field',
} as const

export const LOTDG_PANEL_VARIANT_CODE = {
  NAVIGATION: 'navigation',
  LOCALE: 'locale',
  VITAL_INFO: 'vital-info',
} as const

export type LotdgPanelVariantCode =
  (typeof LOTDG_PANEL_VARIANT_CODE)[keyof typeof LOTDG_PANEL_VARIANT_CODE]

export const LOTDG_PANEL_ROOT_CLASS_NAME: Record<LotdgPanelVariantCode, string> = {
  navigation: 'lotdg-panel lotdg-panel--navigation',
  locale: 'lotdg-panel lotdg-panel--locale',
  'vital-info': 'lotdg-panel lotdg-panel--vital-info',
}

export const LOTDG_PANEL_BODY_CLASS_NAME: Record<LotdgPanelVariantCode, string> = {
  navigation: 'lotdg-panel__body lotdg-panel__body--navigation',
  locale: 'lotdg-panel__body lotdg-panel__body--locale',
  'vital-info': 'lotdg-panel__body lotdg-panel__body--vital-info',
}

export const LOTDG_BUTTON_TONE_CODE = {
  NEUTRAL: 'neutral',
  PRIMARY: 'primary',
  DANGER: 'danger',
} as const

export type LotdgButtonToneCode =
  (typeof LOTDG_BUTTON_TONE_CODE)[keyof typeof LOTDG_BUTTON_TONE_CODE]

export const LOTDG_BUTTON_CLASS_NAME: Record<LotdgButtonToneCode, string> = {
  neutral: 'lotdg-button',
  primary: 'lotdg-button lotdg-button--primary',
  danger: 'lotdg-button lotdg-button--danger',
}

export const LOTDG_CONTROL_WIDTH_CODE = {
  DEFAULT: 'default',
  LOGIN_PANEL: 'login-panel',
} as const

export type LotdgControlWidthCode =
  (typeof LOTDG_CONTROL_WIDTH_CODE)[keyof typeof LOTDG_CONTROL_WIDTH_CODE]

export const LOTDG_CONTROL_WIDTH_CLASS_NAME: Record<LotdgControlWidthCode, string> = {
  default: '',
  'login-panel': 'lotdg-login-panel__field',
}

export const LOTDG_STAGE_CLASS_NAME: Record<LotdgStageSceneCode, string> = {
  none: 'lotdg-shell__stage lotdg-shell__stage--scene-none',
  village: 'lotdg-shell__stage lotdg-shell__stage--scene-village',
  forest: 'lotdg-shell__stage lotdg-shell__stage--scene-forest',
  castle: 'lotdg-shell__stage lotdg-shell__stage--scene-castle',
}
