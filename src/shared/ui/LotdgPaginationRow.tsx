import { LOTDG_BUTTON_TONE_CODE, LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgPaginationRowProps } from '../type/lotdg-ui-component-contract'
import { LotdgButton } from './LotdgButton'

export function LotdgPaginationRow({
  pageCount,
  activePageIndex,
  onPageSelect,
  pageLabelText,
}: LotdgPaginationRowProps) {
  if (pageCount <= 1) {
    return null
  }

  return (
    <div className={LOTDG_UI_CLASS_NAME.PAGINATION}>
      {Array.from({ length: pageCount }, (_unused, pageIndex) => (
        <LotdgButton
          key={pageIndex}
          labelSlot={pageLabelText(pageIndex + 1)}
          toneCode={
            pageIndex === activePageIndex
              ? LOTDG_BUTTON_TONE_CODE.PRIMARY
              : LOTDG_BUTTON_TONE_CODE.NEUTRAL
          }
          isDisabled={pageIndex === activePageIndex}
          onSelect={() => onPageSelect(pageIndex)}
        />
      ))}
    </div>
  )
}
