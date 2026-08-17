import type { ReactNode } from 'react'
import type {
  LotdgButtonToneCode,
  LotdgControlWidthCode,
  LotdgPanelVariantCode,
} from '../constant/lotdg-ui-class-name'
import type { LotdgLegacyColorClassName } from '../constant/lotdg-legacy-color-code'
import type { LotdgNoticeTone } from '../constant/lotdg-notice-tone'
import type { LotdgAccessKey, LotdgAutocompleteToken } from '../constant/lotdg-form-token'
import type { LotdgStageSceneCode } from '../../app/layout/lotdg-stage-scene-code'

export interface LotdgChildrenProps {
  readonly children: ReactNode
}

export interface LotdgButtonProps {
  readonly labelSlot: ReactNode
  readonly onSelect: () => void
  readonly toneCode?: LotdgButtonToneCode
  readonly isDisabled?: boolean
}

export interface LotdgSubmitButtonProps {
  readonly labelSlot: ReactNode
  readonly toneCode?: LotdgButtonToneCode
  readonly isDisabled?: boolean
}

export interface LotdgTextFieldProps {
  readonly labelText?: string
  readonly value: string
  readonly onValueChange: (nextValue: string) => void
  readonly isSecret?: boolean
  readonly isDisabled?: boolean
  readonly maximumLength?: number
  readonly widthCode?: LotdgControlWidthCode
  readonly autocompleteToken?: LotdgAutocompleteToken
  readonly accessKey?: LotdgAccessKey
  readonly placeholderText?: string
  readonly isNumeric?: boolean
}

export interface LotdgTextAreaFieldProps {
  readonly labelText?: string
  readonly value: string
  readonly onValueChange: (nextValue: string) => void
  readonly rowCount?: number
  readonly maximumLength?: number
  readonly isDisabled?: boolean
}

export interface LotdgSelectOption {
  readonly optionValue: string
  readonly labelText: string
}

export interface LotdgSelectFieldProps {
  readonly labelText?: string
  readonly value: string
  readonly optionList: ReadonlyArray<LotdgSelectOption>
  readonly onValueChange: (nextValue: string) => void
  readonly isDisabled?: boolean
  readonly widthCode?: LotdgControlWidthCode
}

export interface LotdgCheckboxFieldProps {
  readonly labelText: string
  readonly isChecked: boolean
  readonly onCheckedChange: (nextChecked: boolean) => void
  readonly isDisabled?: boolean
  readonly isLabelHidden?: boolean
}

export interface LotdgFieldRowProps extends LotdgChildrenProps {
  readonly isStacked?: boolean
}

export interface LotdgActionRowProps extends LotdgChildrenProps {
  readonly isCentered?: boolean
}

export interface LotdgPaginationRowProps {
  readonly pageCount: number
  readonly activePageIndex: number
  readonly onPageSelect: (pageIndex: number) => void
  readonly pageLabelText: (pageNumber: number) => string
}

export interface LotdgFormProps extends LotdgChildrenProps {
  readonly onSubmit: () => void
}

export interface LotdgScreenProps extends LotdgChildrenProps {
  readonly titleText: string
}

export interface LotdgSectionProps extends LotdgChildrenProps {
  readonly titleSlot?: ReactNode
}

export interface LotdgTextProps extends LotdgChildrenProps {
  readonly colorClassName?: LotdgLegacyColorClassName
  readonly isCentered?: boolean
}

export interface LotdgInlineTextProps extends LotdgChildrenProps {
  readonly colorClassName: LotdgLegacyColorClassName
}

export interface LotdgMessageListProps {
  readonly messageTextList: readonly string[]
  readonly colorClassName: LotdgLegacyColorClassName
}

export interface LotdgCommentLineProps {
  readonly authorName: string
  readonly commentText: string
}

export interface LotdgMarkupTextProps {
  readonly sourceText: string
  readonly weaponName?: string
  readonly colorClassName?: LotdgLegacyColorClassName
  readonly isCentered?: boolean
}

export interface LotdgLocaleMenuProps {
  readonly characterId: number | null
}

export interface LotdgShellLayoutProps {
  readonly pageTitle: string
  readonly bannerAlternativeText: string
  readonly headerLinkSlot: ReactNode
  readonly navigationSlot: ReactNode
  readonly preferenceSlot: ReactNode
  readonly characterStatSlot: ReactNode
  readonly stageSlot: ReactNode
  readonly stageSceneCode: LotdgStageSceneCode
  readonly footerSlot: ReactNode
}

export interface LotdgNoticeLineProps {
  readonly messageText: string
  readonly tone?: LotdgNoticeTone
}

export interface LotdgScrollPanelProps extends LotdgChildrenProps {
  readonly variantCode: LotdgPanelVariantCode
}

export interface LotdgNavigationGroupProps extends LotdgChildrenProps {
  readonly headText: string
}

export interface LotdgNoticeLinkProps {
  readonly hashCode: string
  readonly labelText: string
  readonly onSelect: () => void
}

export interface LotdgNavigationItemProps {
  readonly hashCode: string
  readonly labelText: string
  readonly onSelect: () => void
  readonly isCurrent?: boolean
}

export interface LotdgStatEntry {
  readonly entryKey: string
  readonly labelText: string
  readonly valueSlot: ReactNode
}

export interface LotdgStatSection {
  readonly sectionKey: string
  readonly headText?: string
  readonly entryList: ReadonlyArray<LotdgStatEntry>
}

export interface LotdgLinkProps {
  readonly hashCode: string
  readonly labelSlot: ReactNode
  readonly onSelect: () => void
}

export interface LotdgStatBuffEntry {
  readonly buffKey: string
  readonly nameSlot: ReactNode
  readonly roundsText: string
}

export interface LotdgStatBuffListProps {
  readonly titleText: string
  readonly buffList: ReadonlyArray<LotdgStatBuffEntry>
  readonly emptyText: string
}

export interface LotdgStatTableProps {
  readonly sectionList: ReadonlyArray<LotdgStatSection>
  readonly footerSlot?: ReactNode
  readonly isWide?: boolean
}

export interface LotdgDataTableColumn<TRow> {
  readonly columnKey: string
  readonly headText: string
  readonly render: (row: TRow) => ReactNode
}

export interface LotdgDataTableProps<TRow> {
  readonly columnList: ReadonlyArray<LotdgDataTableColumn<TRow>>
  readonly rowList: readonly TRow[]
  readonly rowKey: (row: TRow, rowIndex: number) => string | number
  readonly emptyText?: string
}
