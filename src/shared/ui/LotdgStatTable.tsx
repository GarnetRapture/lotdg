import { Fragment } from 'react'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgStatTableProps } from '../type/lotdg-ui-component-contract'

const LOTDG_STAT_COLUMN_COUNT = 2

export function LotdgStatTable({ sectionList, footerSlot, isWide = false }: LotdgStatTableProps) {
  return (
    <table
      className={isWide ? LOTDG_UI_CLASS_NAME.STAT_TABLE_WIDE : LOTDG_UI_CLASS_NAME.STAT_TABLE}
    >
      <tbody>
        {sectionList.map((section) => (
          <Fragment key={section.sectionKey}>
            <tr>
              <th className={LOTDG_UI_CLASS_NAME.STAT_HEAD} colSpan={LOTDG_STAT_COLUMN_COUNT}>
                {section.headText}
              </th>
            </tr>
            {section.entryList.map((entry) => (
              <tr key={entry.entryKey}>
                <td className={LOTDG_UI_CLASS_NAME.STAT_LABEL}>{entry.labelText}</td>
                <td className={LOTDG_UI_CLASS_NAME.STAT_VALUE}>{entry.valueSlot}</td>
              </tr>
            ))}
          </Fragment>
        ))}
        {footerSlot !== undefined && (
          <tr>
            <td className={LOTDG_UI_CLASS_NAME.STAT_BUFF} colSpan={LOTDG_STAT_COLUMN_COUNT}>
              {footerSlot}
            </td>
          </tr>
        )}
      </tbody>
    </table>
  )
}
