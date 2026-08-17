import { useId } from 'react'
import {
  LOTDG_CONTROL_WIDTH_CLASS_NAME,
  LOTDG_CONTROL_WIDTH_CODE,
  LOTDG_UI_CLASS_NAME,
} from '../constant/lotdg-ui-class-name'
import { joinClassName } from '../lib/lotdg-class-name-joiner'
import type { LotdgTextFieldProps } from '../type/lotdg-ui-component-contract'

export function LotdgTextField({
  labelText,
  value,
  onValueChange,
  isSecret = false,
  isDisabled = false,
  maximumLength,
  widthCode = LOTDG_CONTROL_WIDTH_CODE.DEFAULT,
  autocompleteToken,
  accessKey,
  placeholderText,
  isNumeric = false,
}: LotdgTextFieldProps) {
  const fieldId = useId()

  return (
    <>
      {labelText !== undefined && (
        <label className={LOTDG_UI_CLASS_NAME.FIELD_LABEL} htmlFor={fieldId}>
          {labelText}
        </label>
      )}
      <input
        id={fieldId}
        className={joinClassName(
          LOTDG_UI_CLASS_NAME.INPUT,
          LOTDG_CONTROL_WIDTH_CLASS_NAME[widthCode],
        )}
        type={isSecret ? 'password' : 'text'}
        value={value}
        maxLength={maximumLength}
        disabled={isDisabled}
        autoComplete={autocompleteToken}
        accessKey={accessKey}
        placeholder={placeholderText}
        inputMode={isNumeric ? 'numeric' : undefined}
        onChange={(event) => onValueChange(event.target.value)}
      />
    </>
  )
}
