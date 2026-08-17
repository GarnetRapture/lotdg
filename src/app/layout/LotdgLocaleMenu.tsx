import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { useLotdgLocalePersistence } from '../../i18n/useLotdgLocalePersistence'
import {
  LOTDG_LOCALE_NAMESPACE,
  LOTDG_SUPPORTED_LOCALE_CODE_LIST,
  LOTDG_SUPPORTED_LOCALE_ENDONYM,
} from '../../shared/constant/lotdg-supported-locale'
import type { LotdgLocaleMenuProps } from '../../shared/type/lotdg-ui-component-contract'
import { LotdgNavigationGroup } from '../../shared/ui/LotdgNavigationGroup'
import { LotdgNavigationItem } from '../../shared/ui/LotdgNavigationItem'

const LOTDG_LOCALE_HASH_PREFIX = 'locale-'

export function LotdgLocaleMenu({ characterId }: LotdgLocaleMenuProps) {
  const { translate } = useLotdgLocale()
  const { localeCode, changeLocaleCode } = useLotdgLocalePersistence(characterId)

  return (
    <LotdgNavigationGroup headText={translate(LOTDG_LOCALE_NAMESPACE.NAVIGATION, 'group.language')}>
      {LOTDG_SUPPORTED_LOCALE_CODE_LIST.map((code) => (
        <LotdgNavigationItem
          key={code}
          hashCode={`${LOTDG_LOCALE_HASH_PREFIX}${code}`}
          labelText={LOTDG_SUPPORTED_LOCALE_ENDONYM[code]}
          isCurrent={code === localeCode}
          onSelect={() => changeLocaleCode(code)}
        />
      ))}
    </LotdgNavigationGroup>
  )
}
