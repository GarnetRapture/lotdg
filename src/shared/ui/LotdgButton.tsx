import {
  LOTDG_BUTTON_CLASS_NAME,
  LOTDG_BUTTON_TONE_CODE,
} from '../constant/lotdg-ui-class-name'
import type { LotdgButtonProps } from '../type/lotdg-ui-component-contract'

export function LotdgButton({
  labelSlot,
  onSelect,
  toneCode = LOTDG_BUTTON_TONE_CODE.NEUTRAL,
  isDisabled = false,
}: LotdgButtonProps) {
  return (
    <button
      type="button"
      className={LOTDG_BUTTON_CLASS_NAME[toneCode]}
      disabled={isDisabled}
      onClick={onSelect}
    >
      {labelSlot}
    </button>
  )
}
