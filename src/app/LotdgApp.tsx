import { useCallback, useEffect, useState } from 'react'
import { LotdgShellLayout } from './layout/LotdgShellLayout'
import { LOTDG_STAGE_SCENE_CODE, resolveStageSceneCode } from './layout/lotdg-stage-scene-code'
import { LotdgLocaleProvider } from '../i18n/LotdgLocaleContext'
import { useLotdgLocale } from '../i18n/useLotdgLocale'
import { useLotdgLocalePersistence } from '../i18n/useLotdgLocalePersistence'
import { LotdgSessionProvider } from './provider/LotdgSessionContext'
import { LotdgThemeProvider } from './provider/LotdgThemeContext'
import { useLotdgSession } from './provider/useLotdgSession'
import { LotdgLocaleMenu } from './layout/LotdgLocaleMenu'
import { LotdgThemeMenu } from './layout/LotdgThemeMenu'
import { LOTDG_LOCALE_NAMESPACE } from '../shared/constant/lotdg-supported-locale'
import {
  LOTDG_ATTRIBUTION_TEXT,
  LOTDG_TITLE_BANNER_ALTERNATIVE_TEXT,
} from '../shared/constant/lotdg-attribution'
import { LotdgNavigationGroup } from '../shared/ui/LotdgNavigationGroup'
import { LotdgNavigationItem } from '../shared/ui/LotdgNavigationItem'
import { LotdgNoticeLink } from '../shared/ui/LotdgNoticeLink'
import {
  LOTDG_SCREEN_CODE,
  LOTDG_SCREEN_TITLE_LABEL_PATH,
  type LotdgScreenCode,
} from './router/lotdg-screen-code'
import { LotdgLoginScreen } from '../feature/authentication/LotdgLoginScreen'
import { LotdgRegisterScreen } from '../feature/authentication/LotdgRegisterScreen'
import { LotdgVillageScreen } from '../feature/village/LotdgVillageScreen'
import { LotdgInnScreen } from '../feature/village/LotdgInnScreen'
import { LotdgHealerScreen } from '../feature/village/LotdgHealerScreen'
import { LotdgOuthouseScreen } from '../feature/village/LotdgOuthouseScreen'
import { LotdgForestScreen } from '../feature/forest/LotdgForestScreen'
import { LotdgSpecialEventScreen } from '../feature/forest/LotdgSpecialEventScreen'
import { LotdgDarkHorseScreen } from '../feature/forest/LotdgDarkHorseScreen'
import { LotdgGameInformationScreen } from '../feature/village/LotdgGameInformationScreen'
import { LotdgEquipmentEditorScreen } from '../feature/administration/LotdgEquipmentEditorScreen'
import {
  LOTDG_SPECIAL_EVENT_CODE,
  type LotdgSpecialEventCode,
} from '../shared/constant/lotdg-special-event-code'
import { LotdgTrainingScreen } from '../feature/battle/LotdgTrainingScreen'
import { LotdgGraveyardScreen } from '../feature/battle/LotdgGraveyardScreen'
import { LotdgDragonScreen } from '../feature/battle/LotdgDragonScreen'
import { LotdgPlayerVersusPlayerScreen } from '../feature/battle/LotdgPlayerVersusPlayerScreen'
import { LotdgBankScreen } from '../feature/commerce/LotdgBankScreen'
import { LotdgEquipmentShopScreen } from '../feature/commerce/LotdgEquipmentShopScreen'
import { LotdgMountStableScreen } from '../feature/commerce/LotdgMountStableScreen'
import { LotdgGemTraderScreen } from '../feature/commerce/LotdgGemTraderScreen'
import { LotdgNewsScreen } from '../feature/social/LotdgNewsScreen'
import { LotdgMailScreen } from '../feature/social/LotdgMailScreen'
import { LotdgPetitionScreen } from '../feature/social/LotdgPetitionScreen'
import { LotdgBountyScreen } from '../feature/social/LotdgBountyScreen'
import { LotdgGypsySeerScreen } from '../feature/social/LotdgGypsySeerScreen'
import { LotdgHallOfFameScreen } from '../feature/social/LotdgHallOfFameScreen'
import { LotdgWarriorListScreen } from '../feature/social/LotdgWarriorListScreen'
import { LotdgBiographyScreen } from '../feature/social/LotdgBiographyScreen'
import { LotdgSocialVenueScreen } from '../feature/social/LotdgSocialVenueScreen'
import { LotdgMessageOfTheDayScreen } from '../feature/social/LotdgMessageOfTheDayScreen'
import { LotdgPreferenceScreen } from '../feature/account/LotdgPreferenceScreen'
import { LotdgAdministrationScreen } from '../feature/administration/LotdgAdministrationScreen'
import { LotdgCharacterStatPanel } from '../feature/character/LotdgCharacterStatPanel'
import {
  LOTDG_NEWS_REMOVAL_SUPERUSER_LEVEL,
  LOTDG_SHOP_TYPE_CODE,
  LOTDG_SOCIAL_VENUE_CODE,
} from '../shared/constant/lotdg-legacy-code'

const NAVIGATION_GROUP: ReadonlyArray<{
  readonly headLabelPath: string
  readonly screenCodeList: readonly LotdgScreenCode[]
}> = [
  {
    headLabelPath: 'group.combat',
    screenCodeList: [
      LOTDG_SCREEN_CODE.FOREST,
      LOTDG_SCREEN_CODE.TRAINING,
      LOTDG_SCREEN_CODE.PLAYER_VERSUS_PLAYER,
      LOTDG_SCREEN_CODE.DRAGON,
      LOTDG_SCREEN_CODE.GRAVEYARD,
      LOTDG_SCREEN_CODE.BOUNTY,
    ],
  },
  {
    headLabelPath: 'group.commerce',
    screenCodeList: [
      LOTDG_SCREEN_CODE.WEAPON_SHOP,
      LOTDG_SCREEN_CODE.ARMOR_SHOP,
      LOTDG_SCREEN_CODE.BANK,
      LOTDG_SCREEN_CODE.STABLE,
      LOTDG_SCREEN_CODE.GEM_TRADER,
      LOTDG_SCREEN_CODE.HEALER,
    ],
  },
  {
    headLabelPath: 'group.village',
    screenCodeList: [
      LOTDG_SCREEN_CODE.VILLAGE,
      LOTDG_SCREEN_CODE.INN,
      LOTDG_SCREEN_CODE.GARDENS,
      LOTDG_SCREEN_CODE.VETERANS,
      LOTDG_SCREEN_CODE.GYPSY,
      LOTDG_SCREEN_CODE.OUTHOUSE,
    ],
  },
  {
    headLabelPath: 'group.other',
    screenCodeList: [
      LOTDG_SCREEN_CODE.NEWS,
      LOTDG_SCREEN_CODE.MOTD,
      LOTDG_SCREEN_CODE.MAIL,
      LOTDG_SCREEN_CODE.WARRIOR_LIST,
      LOTDG_SCREEN_CODE.HALL_OF_FAME,
      LOTDG_SCREEN_CODE.PREFERENCE,
      LOTDG_SCREEN_CODE.PETITION,
      LOTDG_SCREEN_CODE.GAME_INFORMATION,
      LOTDG_SCREEN_CODE.ADMINISTRATION,
      LOTDG_SCREEN_CODE.WEAPON_EDITOR,
      LOTDG_SCREEN_CODE.ARMOR_EDITOR,
    ],
  },
]

const LOTDG_LOGOUT_HASH_CODE = 'logout'

const HEADER_LINK_SCREEN_CODE_LIST: readonly LotdgScreenCode[] = [
  LOTDG_SCREEN_CODE.MOTD,
  LOTDG_SCREEN_CODE.MAIL,
  LOTDG_SCREEN_CODE.PETITION,
]

function LotdgAppBody() {
  const { session, signOut } = useLotdgSession()
  const { translate } = useLotdgLocale()
  const { applyStoredLocaleCode } = useLotdgLocalePersistence(session?.characterId ?? null)

  useEffect(() => {
    applyStoredLocaleCode(session?.storedLocaleCode)
  }, [applyStoredLocaleCode, session?.storedLocaleCode])

  const [screenCode, setScreenCode] = useState<LotdgScreenCode>(LOTDG_SCREEN_CODE.LOGIN)
  const [refreshToken, setRefreshToken] = useState(0)
  const [biographyCharacterId, setBiographyCharacterId] = useState<number | null>(null)
  const [specialEventCode, setSpecialEventCode] = useState<LotdgSpecialEventCode>(
    LOTDG_SPECIAL_EVENT_CODE.FIND_GEM,
  )

  const openBiography = useCallback((targetCharacterId: number) => {
    setBiographyCharacterId(targetCharacterId)
    setScreenCode(LOTDG_SCREEN_CODE.BIOGRAPHY)
  }, [])

  const refreshCharacter = useCallback(() => {
    setRefreshToken((previous) => previous + 1)
  }, [])

  const navigationLabel = (path: string) => translate(LOTDG_LOCALE_NAMESPACE.NAVIGATION, path)

  const renderStage = () => {
    if (session === null) {
      return screenCode === LOTDG_SCREEN_CODE.REGISTER ? (
        <LotdgRegisterScreen onLoginClick={() => setScreenCode(LOTDG_SCREEN_CODE.LOGIN)} />
      ) : (
        <LotdgLoginScreen onRegisterClick={() => setScreenCode(LOTDG_SCREEN_CODE.REGISTER)} />
      )
    }

    switch (screenCode) {
      case LOTDG_SCREEN_CODE.FOREST:
        return (
          <LotdgForestScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
            onSpecialEventOpen={(eventCode) => {
              if (eventCode === LOTDG_SPECIAL_EVENT_CODE.DARK_HORSE) {
                setScreenCode(LOTDG_SCREEN_CODE.DARK_HORSE)

                return
              }

              setSpecialEventCode(eventCode)
              setScreenCode(LOTDG_SCREEN_CODE.SPECIAL_EVENT)
            }}
          />
        )
      case LOTDG_SCREEN_CODE.DARK_HORSE:
        return (
          <LotdgDarkHorseScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
            onLeave={() => setScreenCode(LOTDG_SCREEN_CODE.FOREST)}
          />
        )
      case LOTDG_SCREEN_CODE.GAME_INFORMATION:
        return (
          <LotdgGameInformationScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.WEAPON_EDITOR:
        return (
          <LotdgEquipmentEditorScreen
            characterId={session.characterId}
            shopType={LOTDG_SHOP_TYPE_CODE.WEAPON}
          />
        )
      case LOTDG_SCREEN_CODE.ARMOR_EDITOR:
        return (
          <LotdgEquipmentEditorScreen
            characterId={session.characterId}
            shopType={LOTDG_SHOP_TYPE_CODE.ARMOR}
          />
        )
      case LOTDG_SCREEN_CODE.SPECIAL_EVENT:
        return (
          <LotdgSpecialEventScreen
            characterId={session.characterId}
            eventCode={specialEventCode}
            onStateChange={refreshCharacter}
            onLeave={() => setScreenCode(LOTDG_SCREEN_CODE.FOREST)}
          />
        )
      case LOTDG_SCREEN_CODE.TRAINING:
        return (
          <LotdgTrainingScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.BANK:
        return (
          <LotdgBankScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.WEAPON_SHOP:
        return (
          <LotdgEquipmentShopScreen
            characterId={session.characterId}
            shopType={LOTDG_SHOP_TYPE_CODE.WEAPON}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.ARMOR_SHOP:
        return (
          <LotdgEquipmentShopScreen
            characterId={session.characterId}
            shopType={LOTDG_SHOP_TYPE_CODE.ARMOR}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.INN:
        return <LotdgInnScreen characterId={session.characterId} onStateChange={refreshCharacter} />
      case LOTDG_SCREEN_CODE.HEALER:
        return (
          <LotdgHealerScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.OUTHOUSE:
        return (
          <LotdgOuthouseScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.STABLE:
        return (
          <LotdgMountStableScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.GEM_TRADER:
        return (
          <LotdgGemTraderScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.GRAVEYARD:
        return (
          <LotdgGraveyardScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.DRAGON:
        return (
          <LotdgDragonScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.PLAYER_VERSUS_PLAYER:
        return (
          <LotdgPlayerVersusPlayerScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.BOUNTY:
        return (
          <LotdgBountyScreen characterId={session.characterId} onStateChange={refreshCharacter} />
        )
      case LOTDG_SCREEN_CODE.GYPSY:
        return (
          <LotdgGypsySeerScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.HALL_OF_FAME:
        return <LotdgHallOfFameScreen />
      case LOTDG_SCREEN_CODE.WARRIOR_LIST:
        return <LotdgWarriorListScreen onBiographyOpen={openBiography} />
      case LOTDG_SCREEN_CODE.BIOGRAPHY:
        return <LotdgBiographyScreen characterId={biographyCharacterId ?? session.characterId} />
      case LOTDG_SCREEN_CODE.GARDENS:
        return (
          <LotdgSocialVenueScreen
            characterId={session.characterId}
            venueCode={LOTDG_SOCIAL_VENUE_CODE.GARDENS}
          />
        )
      case LOTDG_SCREEN_CODE.VETERANS:
        return (
          <LotdgSocialVenueScreen
            characterId={session.characterId}
            venueCode={LOTDG_SOCIAL_VENUE_CODE.VETERANS}
          />
        )
      case LOTDG_SCREEN_CODE.MOTD:
        return <LotdgMessageOfTheDayScreen characterId={session.characterId} />
      case LOTDG_SCREEN_CODE.PREFERENCE:
        return (
          <LotdgPreferenceScreen
            characterId={session.characterId}
            onStateChange={refreshCharacter}
          />
        )
      case LOTDG_SCREEN_CODE.NEWS:
        return (
          <LotdgNewsScreen
            characterId={session.characterId}
            canRemove={session.superuserLevel >= LOTDG_NEWS_REMOVAL_SUPERUSER_LEVEL}
          />
        )
      case LOTDG_SCREEN_CODE.MAIL:
        return <LotdgMailScreen characterId={session.characterId} />
      case LOTDG_SCREEN_CODE.PETITION:
        return <LotdgPetitionScreen characterId={session.characterId} />
      case LOTDG_SCREEN_CODE.ADMINISTRATION:
        return <LotdgAdministrationScreen characterId={session.characterId} />
      default:
        return <LotdgVillageScreen characterId={session.characterId} />
    }
  }

  const pageTitle = translate(
    LOTDG_LOCALE_NAMESPACE.NAVIGATION,
    LOTDG_SCREEN_TITLE_LABEL_PATH[screenCode],
  )

  return (
    <LotdgShellLayout
      pageTitle={pageTitle}
      bannerAlternativeText={LOTDG_TITLE_BANNER_ALTERNATIVE_TEXT}
      headerLinkSlot={
        session === null ? null : (
          <>
            {HEADER_LINK_SCREEN_CODE_LIST.map((code) => (
              <LotdgNoticeLink
                key={code}
                hashCode={code}
                labelText={navigationLabel(LOTDG_SCREEN_TITLE_LABEL_PATH[code])}
                onSelect={() => setScreenCode(code)}
              />
            ))}
          </>
        )
      }
      navigationSlot={
        <>
          {session !== null &&
            NAVIGATION_GROUP.map((group) => (
              <LotdgNavigationGroup
                key={group.headLabelPath}
                headText={navigationLabel(group.headLabelPath)}
              >
                {group.screenCodeList.map((code) => (
                  <LotdgNavigationItem
                    key={code}
                    hashCode={code}
                    labelText={navigationLabel(LOTDG_SCREEN_TITLE_LABEL_PATH[code])}
                    isCurrent={code === screenCode}
                    onSelect={() => setScreenCode(code)}
                  />
                ))}
              </LotdgNavigationGroup>
            ))}

          {session !== null && (
            <LotdgNavigationItem
              hashCode={LOTDG_LOGOUT_HASH_CODE}
              labelText={navigationLabel('action.logout')}
              onSelect={() => {
                signOut()
                setScreenCode(LOTDG_SCREEN_CODE.LOGIN)
              }}
            />
          )}
        </>
      }
      preferenceSlot={
        <>
          <LotdgLocaleMenu characterId={session?.characterId ?? null} />
          <LotdgThemeMenu />
        </>
      }
      characterStatSlot={
        session === null ? null : (
          <LotdgCharacterStatPanel characterId={session.characterId} refreshToken={refreshToken} />
        )
      }
      stageSlot={renderStage()}
      stageSceneCode={
        session === null ? LOTDG_STAGE_SCENE_CODE.NONE : resolveStageSceneCode(screenCode)
      }
      footerSlot={<small>{LOTDG_ATTRIBUTION_TEXT}</small>}
    />
  )
}

export function LotdgApp() {
  return (
    <LotdgThemeProvider>
      <LotdgLocaleProvider>
        <LotdgSessionProvider>
          <LotdgAppBody />
        </LotdgSessionProvider>
      </LotdgLocaleProvider>
    </LotdgThemeProvider>
  )
}
