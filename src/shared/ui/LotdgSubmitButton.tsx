import {
  LOTDG_BUTTON_CLASS_NAME,
  LOTDG_BUTTON_TONE_CODE,
} from '../constant/lotdg-ui-class-name'
import type { LotdgSubmitButtonProps } from '../type/lotdg-ui-component-contract'

export function LotdgSubmitButton({
  labelSlot,
  toneCode = LOTDG_BUTTON_TONE_CODE.NEUTRAL,
  isDisabled = false,
}: LotdgSubmitButtonProps) {
  return (
    <button type="submit" className={LOTDG_BUTTON_CLASS_NAME[toneCode]} disabled={isDisabled}>
      {labelSlot}
    </button>
  )
}
