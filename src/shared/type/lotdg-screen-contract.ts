export type LotdgTranslateFunction = (
  namespaceCode: string,
  labelPath: string,
  valueMap?: Record<string, string | number>,
) => string

export type LotdgLabelFunction = (
  labelPath: string,
  valueMap?: Record<string, string | number>,
) => string

export interface LotdgCharacterScreenProps {
  readonly characterId: number
}

export interface LotdgMutableScreenProps extends LotdgCharacterScreenProps {
  readonly onStateChange: () => void
}

export interface LotdgFormFieldValueMap {
  readonly [fieldName: string]: string | number
}
