import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_THEME_CODE_LIST,
  LOTDG_THEME_LABEL_PATH,
} from '../../shared/constant/lotdg-theme-code'
import { LotdgNavigationGroup } from '../../shared/ui/LotdgNavigationGroup'
import { LotdgNavigationItem } from '../../shared/ui/LotdgNavigationItem'
import { useLotdgTheme } from '../provider/useLotdgTheme'

const LOTDG_THEME_HASH_PREFIX = 'theme-'

export function LotdgThemeMenu() {
  const { translate } = useLotdgLocale()
  const { themeCode, changeThemeCode } = useLotdgTheme()

  return (
    <LotdgNavigationGroup headText={translate(LOTDG_LOCALE_NAMESPACE.NAVIGATION, 'group.theme')}>
      {LOTDG_THEME_CODE_LIST.map((code) => (
        <LotdgNavigationItem
          key={code}
          hashCode={`${LOTDG_THEME_HASH_PREFIX}${code}`}
          labelText={translate(LOTDG_LOCALE_NAMESPACE.NAVIGATION, LOTDG_THEME_LABEL_PATH[code])}
          isCurrent={code === themeCode}
          onSelect={() => changeThemeCode(code)}
        />
      ))}
    </LotdgNavigationGroup>
  )
}
