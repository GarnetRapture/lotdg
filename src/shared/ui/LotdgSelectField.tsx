import { useId } from 'react'
import {
  LOTDG_CONTROL_WIDTH_CLASS_NAME,
  LOTDG_CONTROL_WIDTH_CODE,
  LOTDG_UI_CLASS_NAME,
} from '../constant/lotdg-ui-class-name'
import { joinClassName } from '../lib/lotdg-class-name-joiner'
import type { LotdgSelectFieldProps } from '../type/lotdg-ui-component-contract'

export function LotdgSelectField({
  labelText,
  value,
  optionList,
  onValueChange,
  isDisabled = false,
  widthCode = LOTDG_CONTROL_WIDTH_CODE.DEFAULT,
}: LotdgSelectFieldProps) {
  const fieldId = useId()

  return (
    <>
      {labelText !== undefined && (
        <label className={LOTDG_UI_CLASS_NAME.FIELD_LABEL} htmlFor={fieldId}>
          {labelText}
        </label>
      )}
      <select
        id={fieldId}
        className={joinClassName(
          LOTDG_UI_CLASS_NAME.SELECT,
          LOTDG_CONTROL_WIDTH_CLASS_NAME[widthCode],
        )}
        value={value}
        disabled={isDisabled}
        onChange={(event) => onValueChange(event.target.value)}
      >
        {optionList.map((option) => (
          <option key={option.optionValue} value={option.optionValue}>
            {option.labelText}
          </option>
        ))}
      </select>
    </>
  )
}
