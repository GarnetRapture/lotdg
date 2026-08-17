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

export function LotdgEquipmentShopScreen({
  characterId,
  shopType,
  onStateChange,
}: {
  readonly characterId: number
  readonly shopType: 'weapon' | 'armor'
  readonly onStateChange: () => void
}) {
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

  return (
    <section>
      <h2>{label(shopType === 'weapon' ? 'shop.weapon.title' : 'shop.armor.title')}</h2>

      {browse !== null && (
        <>
          <p>
            {label('shop.trade-in', {
              value: browse.trade_in_value,
              gold: browse.gold,
            })}
          </p>

          <table className="lotdg-stat">
            <tbody>
              <tr>
                <th className="lotdg-stat__head">{label('shop.column.name')}</th>
                <th className="lotdg-stat__head">
                  {label(shopType === 'weapon' ? 'shop.column.damage' : 'shop.column.defense')}
                </th>
                <th className="lotdg-stat__head">{label('shop.column.price')}</th>
                <th className="lotdg-stat__head">{label('shop.column.action')}</th>
              </tr>
              {browse.item_list.map((item) => (
                <tr key={item.item_id}>
                  <td className="lotdg-stat__value">{item.item_name}</td>
                  <td className="lotdg-stat__value">{item.power}</td>
                  <td className="lotdg-stat__value">{item.price}</td>
                  <td className="lotdg-stat__value">
                    <button
                      type="button"
                      className="lotdg-button"
                      onClick={() => void buy(item.item_id)}
                      disabled={!item.affordable}
                    >
                      {label('shop.action.buy')}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}

      {message !== '' && <p className="colLtYellow">{message}</p>}
    </section>
  )
}
