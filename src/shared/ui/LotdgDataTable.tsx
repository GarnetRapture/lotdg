import type { ReactNode } from 'react'

export interface LotdgDataTableColumn<TRow> {
  readonly columnKey: string
  readonly headText: string
  readonly render: (row: TRow) => ReactNode
}

export function LotdgDataTable<TRow>({
  columnList,
  rowList,
  rowKey,
  emptyText,
}: {
  readonly columnList: ReadonlyArray<LotdgDataTableColumn<TRow>>
  readonly rowList: readonly TRow[]
  readonly rowKey: (row: TRow, rowIndex: number) => string | number
  readonly emptyText?: string
}) {
  if (rowList.length === 0) {
    return emptyText === undefined ? null : <p className="colDkWhite">{emptyText}</p>
  }

  return (
    <div className="lotdg-table-scroll">
      <table className="lotdg-table">
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
