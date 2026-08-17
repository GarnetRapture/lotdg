import { useCallback, useEffect, useState } from 'react'
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
import type { LotdgEquipmentEditorScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgForm,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSelectField,
  LotdgSubmitButton,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_EDITOR_NEW_ITEM_ID = 0

export function LotdgEquipmentEditorScreen({
  characterId,
  shopType,
}: LotdgEquipmentEditorScreenProps) {
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

      setItemId(LOTDG_EDITOR_NEW_ITEM_ID)
      setItemName('')
      setPower(result.next_power)
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const save = async () => {
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
    <LotdgScreen titleText={label(`equipment-editor.title.${shopType}`)}>
      {editor !== null && (
        <>
          <LotdgActionRow>
            {Array.from({ length: editor.maximum_tier + 1 }, (_unused, tier) => (
              <LotdgButton
                key={tier}
                labelSlot={label('equipment-editor.tier', { tier })}
                isDisabled={dragonKillTier === tier}
                onSelect={() => setDragonKillTier(tier)}
              />
            ))}
          </LotdgActionRow>

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
                  <LotdgActionRow>
                    <LotdgButton
                      labelSlot={label('equipment-editor.action.edit')}
                      onSelect={() => {
                        setItemId(item.item_id)
                        setItemName(item.item_name)
                        setPower(item.power)
                      }}
                    />
                    <LotdgButton
                      labelSlot={label('equipment-editor.action.remove')}
                      onSelect={() => void remove(item.item_id)}
                    />
                  </LotdgActionRow>
                ),
              },
            ]}
          />

          <LotdgForm onSubmit={() => void save()}>
            <LotdgActionRow>
              <LotdgButton
                labelSlot={label('equipment-editor.action.add')}
                onSelect={() => void loadNextPower()}
              />
            </LotdgActionRow>

            <LotdgFieldRow>
              <LotdgTextField
                labelText={label('equipment-editor.column.name')}
                value={itemName}
                onValueChange={setItemName}
              />
              <LotdgSelectField
                labelText={label(`equipment-editor.column.power.${shopType}`)}
                value={String(power)}
                onValueChange={(nextValue) => setPower(Number(nextValue))}
                optionList={Array.from(
                  { length: editor.maximum_power - editor.minimum_power + 1 },
                  (_unused, index) => editor.minimum_power + index,
                ).map((powerValue) => ({
                  optionValue: String(powerValue),
                  labelText: `${powerValue} (${editor.price_by_power[String(powerValue)] ?? 0})`,
                }))}
              />
              <LotdgSubmitButton labelSlot={label('equipment-editor.action.save')} />
            </LotdgFieldRow>
          </LotdgForm>
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
