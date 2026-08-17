import { useId } from 'react'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgTextAreaFieldProps } from '../type/lotdg-ui-component-contract'

const LOTDG_TEXT_AREA_DEFAULT_ROW_COUNT = 4

export function LotdgTextAreaField({
  labelText,
  value,
  onValueChange,
  rowCount = LOTDG_TEXT_AREA_DEFAULT_ROW_COUNT,
  maximumLength,
  isDisabled = false,
}: LotdgTextAreaFieldProps) {
  const fieldId = useId()

  return (
    <>
      {labelText !== undefined && (
        <label className={LOTDG_UI_CLASS_NAME.FIELD_LABEL} htmlFor={fieldId}>
          {labelText}
        </label>
      )}
      <textarea
        id={fieldId}
        className={LOTDG_UI_CLASS_NAME.TEXTAREA}
        rows={rowCount}
        value={value}
        maxLength={maximumLength}
        disabled={isDisabled}
        onChange={(event) => onValueChange(event.target.value)}
      />
    </>
  )
}
