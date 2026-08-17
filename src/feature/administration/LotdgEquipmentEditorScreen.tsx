import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgEquipmentEditorListSchema,
  lotdgEquipmentEditorMutationSchema,
  lotdgEquipmentEditorNextPowerSchema,
  type LotdgEquipmentEditorList,
} from '../../shared/schema/catalog/lotdg-editor-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgCharacterScreenProps } from '../../shared/type/lotdg-screen-contract'

export function LotdgEquipmentEditorScreen({
  characterId,
  shopType,
}: LotdgCharacterScreenProps & { readonly shopType: 'weapon' | 'armor' }) {
  const { translate } = useLotdgLocale()
  const [editor, setEditor] = useState<LotdgEquipmentEditorList | null>(null)
  const [dragonKillTier, setDragonKillTier] = useState(0)
  const [itemId, setItemId] = useState(0)
  const [itemName, setItemName] = useState('')
  const [power, setPower] = useState(1)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(
      `/equipment-editor/${shopType}/${characterId}/list?dragon_kill_tier=${dragonKillTier}`,
      lotdgEquipmentEditorListSchema,
    )
      .then(setEditor)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, shopType, dragonKillTier, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMON, path, valueMap)

  const loadNextPower = async () => {
    try {
      const result = await getJson(
        `/equipment-editor/${shopType}/${characterId}/next-power?dragon_kill_tier=${dragonKillTier}`,
        lotdgEquipmentEditorNextPowerSchema,
      )

      setItemId(0)
      setItemName('')
      setPower(result.next_power)
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const save = async (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()

    try {
      const result = await postForm(
        `/equipment-editor/${shopType}/${characterId}/save`,
        lotdgEquipmentEditorMutationSchema,
        {
          item_id: itemId,
          dragon_kill_tier: dragonKillTier,
          item_name: itemName,
          power,
        },
      )

      setMessage(
        result.saved === true
          ? label('equipment-editor.saved', { price: result.price ?? 0 })
          : resolveMessageKeyLabel(result.message_key, translate),
      )

      if (result.saved === true) {
        setItemId(0)
        setItemName('')
      }

      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const remove = async (targetItemId: number) => {
    try {
      await postForm(
        `/equipment-editor/${shopType}/${characterId}/remove`,
        lotdgEquipmentEditorMutationSchema,
        { item_id: targetItemId },
      )

      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label(`equipment-editor.title.${shopType}`)}</h2>

      {editor !== null && (
        <>
          <p>
            {Array.from({ length: editor.maximum_tier + 1 }, (_unused, tier) => (
              <button
                key={tier}
                type="button"
                className="lotdg-button"
                disabled={dragonKillTier === tier}
                onClick={() => setDragonKillTier(tier)}
              >
                {label('equipment-editor.tier', { tier })}
              </button>
            ))}
          </p>

          <LotdgDataTable
            rowList={editor.item_list}
            rowKey={(item) => item.item_id}
            emptyText={label('equipment-editor.empty')}
            columnList={[
              {
                columnKey: 'name',
                headText: label('equipment-editor.column.name'),
                render: (item) => item.item_name,
              },
              {
                columnKey: 'power',
                headText: label(`equipment-editor.column.power.${shopType}`),
                render: (item) => item.power,
              },
              {
                columnKey: 'price',
                headText: label('equipment-editor.column.price'),
                render: (item) => item.price,
              },
              {
                columnKey: 'action',
                headText: label('equipment-editor.column.action'),
                render: (item) => (
                  <>
                    <button
                      type="button"
                      className="lotdg-button"
                      onClick={() => {
                        setItemId(item.item_id)
                        setItemName(item.item_name)
                        setPower(item.power)
                      }}
                    >
                      {label('equipment-editor.action.edit')}
                    </button>{' '}
                    <button
                      type="button"
                      className="lotdg-button"
                      onClick={() => void remove(item.item_id)}
                    >
                      {label('equipment-editor.action.remove')}
                    </button>
                  </>
                ),
              },
            ]}
          />

          <form onSubmit={(submitEvent) => void save(submitEvent)}>
            <p>
              <button type="button" className="lotdg-button" onClick={() => void loadNextPower()}>
                {label('equipment-editor.action.add')}
              </button>
            </p>
            <p>
              <label htmlFor="lotdg-equipment-name">
                {label('equipment-editor.column.name')}
              </label>{' '}
              <input
                id="lotdg-equipment-name"
                className="lotdg-input"
                value={itemName}
                onChange={(changeEvent) => setItemName(changeEvent.target.value)}
              />{' '}
              <label htmlFor="lotdg-equipment-power">
                {label(`equipment-editor.column.power.${shopType}`)}
              </label>{' '}
              <select
                id="lotdg-equipment-power"
                className="lotdg-select"
                value={power}
                onChange={(changeEvent) => setPower(Number(changeEvent.target.value))}
              >
                {Array.from(
                  { length: editor.maximum_power - editor.minimum_power + 1 },
                  (_unused, index) => editor.minimum_power + index,
                ).map((powerValue) => (
                  <option key={powerValue} value={powerValue}>
                    {powerValue} ({editor.price_by_power[String(powerValue)] ?? 0})
                  </option>
                ))}
              </select>{' '}
              <button type="submit" className="lotdg-button">
                {label('equipment-editor.action.save')}
              </button>
            </p>
          </form>
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
