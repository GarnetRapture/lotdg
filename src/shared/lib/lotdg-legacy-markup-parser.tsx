import type { ReactNode } from 'react'
import {
  LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME,
  LOTDG_LEGACY_COLOR_RESET_CODE,
  LOTDG_LEGACY_ESCAPE_CHARACTER,
  LOTDG_LEGACY_FORMAT_CODE,
  type LotdgLegacyColorCode,
} from '../constant/lotdg-legacy-color-code'

interface ParserState {
  colorClassName: string | null
  isBold: boolean
  isItalic: boolean
  isCentered: boolean
  isHotkey: boolean
}

function wrap(text: string, state: ParserState, key: number): ReactNode {
  if (text === '') {
    return null
  }

  let node: ReactNode = text

  if (state.isHotkey) {
    node = <span className="navhi">{node}</span>
  }

  if (state.isItalic) {
    node = <i>{node}</i>
  }

  if (state.isBold) {
    node = <b>{node}</b>
  }

  if (state.colorClassName !== null) {
    node = <span className={state.colorClassName}>{node}</span>
  }

  if (state.isCentered) {
    node = <div style={{ textAlign: 'center' }}>{node}</div>
  }

  return <span key={key}>{node}</span>
}

function isColorCode(code: string): code is LotdgLegacyColorCode {
  return Object.hasOwn(LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME, code)
}

export function parseLegacyMarkup(source: string, weaponName = ''): ReactNode[] {
  const nodeList: ReactNode[] = []
  const state: ParserState = {
    colorClassName: null,
    isBold: false,
    isItalic: false,
    isCentered: false,
    isHotkey: false,
  }

  let buffer = ''
  let keyCounter = 0

  const flush = () => {
    const node = wrap(buffer, state, keyCounter++)
    if (node !== null) {
      nodeList.push(node)
    }
    buffer = ''
  }

  for (let index = 0; index < source.length; index += 1) {
    const character = source[index]

    if (character !== LOTDG_LEGACY_ESCAPE_CHARACTER || index + 1 >= source.length) {
      buffer += character
      continue
    }

    const code = source[index + 1]
    index += 1

    if (isColorCode(code)) {
      flush()
      state.colorClassName = LOTDG_LEGACY_COLOR_CODE_TO_CLASS_NAME[code]
      continue
    }

    switch (code) {
      case LOTDG_LEGACY_COLOR_RESET_CODE:
        flush()
        state.colorClassName = null
        break
      case LOTDG_LEGACY_FORMAT_CODE.BOLD_TOGGLE:
        flush()
        state.isBold = !state.isBold
        break
      case LOTDG_LEGACY_FORMAT_CODE.ITALIC_TOGGLE:
        flush()
        state.isItalic = !state.isItalic
        break
      case LOTDG_LEGACY_FORMAT_CODE.CENTER_TOGGLE:
        flush()
        state.isCentered = !state.isCentered
        break
      case LOTDG_LEGACY_FORMAT_CODE.NAVIGATION_HOTKEY_TOGGLE:
        flush()
        state.isHotkey = !state.isHotkey
        break
      case LOTDG_LEGACY_FORMAT_CODE.LINE_BREAK:
        flush()
        nodeList.push(<br key={keyCounter++} />)
        break
      case LOTDG_LEGACY_FORMAT_CODE.CURRENT_WEAPON_NAME:
        buffer += weaponName
        break
      case LOTDG_LEGACY_FORMAT_CODE.LITERAL_BACKTICK:
        buffer += LOTDG_LEGACY_ESCAPE_CHARACTER
        break
      default:
        buffer += LOTDG_LEGACY_ESCAPE_CHARACTER + code
    }
  }

  flush()

  return nodeList
}
