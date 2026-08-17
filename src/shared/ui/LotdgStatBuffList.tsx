import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../constant/lotdg-legacy-color-code'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgStatBuffListProps } from '../type/lotdg-ui-component-contract'
import { LotdgInlineText } from './LotdgInlineText'

export function LotdgStatBuffList({ titleText, buffList, emptyText }: LotdgStatBuffListProps) {
  return (
    <>
      <b>{titleText}</b>
      <br />
      {buffList.length === 0 ? (
        <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_YELLOW}>
          {emptyText}
        </LotdgInlineText>
      ) : (
        buffList.map((buff) => (
          <span key={buff.buffKey} className={LOTDG_UI_CLASS_NAME.STAT_BUFF_ROW}>
            {buff.nameSlot}{' '}
            <LotdgInlineText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {buff.roundsText}
            </LotdgInlineText>
          </span>
        ))
      )}
    </>
  )
}
