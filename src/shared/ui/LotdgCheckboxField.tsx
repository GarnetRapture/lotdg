import { useId } from 'react'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgCheckboxFieldProps } from '../type/lotdg-ui-component-contract'

export function LotdgCheckboxField({
  labelText,
  isChecked,
  onCheckedChange,
  isDisabled = false,
}: LotdgCheckboxFieldProps) {
  const fieldId = useId()

  return (
    <>
      <input
        id={fieldId}
        className={LOTDG_UI_CLASS_NAME.CHECKBOX}
        type="checkbox"
        checked={isChecked}
        disabled={isDisabled}
        onChange={(event) => onCheckedChange(event.target.checked)}
      />
      <label className={LOTDG_UI_CLASS_NAME.FIELD_LABEL} htmlFor={fieldId}>
        {labelText}
      </label>
    </>
  )
}
