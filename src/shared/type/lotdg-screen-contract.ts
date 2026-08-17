import type { LotdgCommentarySectionCode } from '../constant/lotdg-commentary-section-code'
import type { LotdgShopTypeCode, LotdgSocialVenueCode } from '../constant/lotdg-legacy-code'
import type { LotdgSpecialEventCode } from '../constant/lotdg-special-event-code'

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

export interface LotdgSpecialEventScreenProps extends LotdgMutableScreenProps {
  readonly eventCode: LotdgSpecialEventCode
  readonly onLeave: () => void
}

export interface LotdgDarkHorseScreenProps extends LotdgMutableScreenProps {
  readonly onLeave: () => void
}

export interface LotdgNewsScreenProps {
  readonly characterId: number | null
  readonly canRemove: boolean
}

export interface LotdgWarriorListScreenProps {
  readonly onBiographyOpen: (characterId: number) => void
}

export interface LotdgForestScreenProps extends LotdgMutableScreenProps {
  readonly onSpecialEventOpen: (eventCode: LotdgSpecialEventCode) => void
}

export interface LotdgLoginScreenProps {
  readonly onRegisterClick: () => void
}

export interface LotdgRegisterScreenProps {
  readonly onLoginClick: () => void
}

export interface LotdgSocialVenueScreenProps extends LotdgCharacterScreenProps {
  readonly venueCode: LotdgSocialVenueCode
}

export interface LotdgEquipmentShopScreenProps extends LotdgMutableScreenProps {
  readonly shopType: LotdgShopTypeCode
}

export interface LotdgEquipmentEditorScreenProps extends LotdgCharacterScreenProps {
  readonly shopType: LotdgShopTypeCode
}

export interface LotdgCommentaryBoardProps extends LotdgCharacterScreenProps {
  readonly sectionCode: LotdgCommentarySectionCode
}

export interface LotdgFormFieldValueMap {
  readonly [fieldName: string]: string | number
}
