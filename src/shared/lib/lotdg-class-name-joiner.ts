export function joinClassName(...classNameList: ReadonlyArray<string | undefined>): string {
  return classNameList.filter((className) => className !== undefined && className !== '').join(' ')
}
