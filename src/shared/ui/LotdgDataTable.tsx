import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../constant/lotdg-legacy-color-code'
import { LOTDG_UI_CLASS_NAME } from '../constant/lotdg-ui-class-name'
import type { LotdgDataTableProps } from '../type/lotdg-ui-component-contract'
import { LotdgText } from './LotdgText'

export function LotdgDataTable<TRow>({
  columnList,
  rowList,
  rowKey,
  emptyText,
}: LotdgDataTableProps<TRow>) {
  if (rowList.length === 0) {
    return emptyText === undefined ? null : (
      <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>{emptyText}</LotdgText>
    )
  }

  return (
    <div className={LOTDG_UI_CLASS_NAME.TABLE_SCROLL}>
      <table className={LOTDG_UI_CLASS_NAME.TABLE_ROOT}>
        <thead>
          <tr>
            {columnList.map((column) => (
              <th key={column.columnKey}>{column.headText}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rowList.map((row, rowIndex) => (
            <tr key={rowKey(row, rowIndex)}>
              {columnList.map((column) => (
                <td key={column.columnKey}>{column.render(row)}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
