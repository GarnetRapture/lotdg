import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgShopBrowseSchema,
  lotdgShopBuySchema,
  type LotdgShopBrowse,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_SHOP_TYPE_CODE } from '../../shared/constant/lotdg-legacy-code'
import type { LotdgEquipmentShopScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgButton, LotdgDataTable, LotdgNoticeLine, LotdgScreen, LotdgText } from '../../shared/ui'

export function LotdgEquipmentShopScreen({
  characterId,
  shopType,
  onStateChange,
}: LotdgEquipmentShopScreenProps) {
  const { translate } = useLotdgLocale()
  const [browse, setBrowse] = useState<LotdgShopBrowse | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/shop/${shopType}/${characterId}/browse`, lotdgShopBrowseSchema)
      .then(setBrowse)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, shopType, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMERCE, path, valueMap)

  const buy = async (itemId: number) => {
    try {
      const result = await postForm(`/shop/${shopType}/${characterId}/buy`, lotdgShopBuySchema, {
        item_id: itemId,
      })

      setMessage(
        result.succeeded
          ? label('shop.bought', { name: result.item_name ?? '', price: result.price ?? 0 })
          : resolveMessageKeyLabel(result.message_key, translate),
      )

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const isWeaponShop = shopType === LOTDG_SHOP_TYPE_CODE.WEAPON

  return (
    <LotdgScreen titleText={label(isWeaponShop ? 'shop.weapon.title' : 'shop.armor.title')}>
      {browse !== null && (
        <>
          <LotdgText>
            {label('shop.trade-in', {
              value: browse.trade_in_value,
              gold: browse.gold,
            })}
          </LotdgText>

          <LotdgDataTable
            rowList={browse.item_list}
            rowKey={(item) => item.item_id}
            columnList={[
              {
                columnKey: 'name',
                headText: label('shop.column.name'),
                render: (item) => item.item_name,
              },
              {
                columnKey: 'power',
                headText: label(isWeaponShop ? 'shop.column.damage' : 'shop.column.defense'),
                render: (item) => item.power,
              },
              {
                columnKey: 'price',
                headText: label('shop.column.price'),
                render: (item) => item.price,
              },
              {
                columnKey: 'action',
                headText: label('shop.column.action'),
                render: (item) => (
                  <LotdgButton
                    labelSlot={label('shop.action.buy')}
                    isDisabled={!item.affordable}
                    onSelect={() => void buy(item.item_id)}
                  />
                ),
              },
            ]}
          />
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
