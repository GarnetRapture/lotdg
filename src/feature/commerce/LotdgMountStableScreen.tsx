import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgStableBrowseSchema,
  lotdgStableMutationSchema,
  type LotdgStableBrowse,
} from '../../shared/schema/world/lotdg-world-response-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'
import { LotdgCommentaryBoard } from '../social/LotdgCommentaryBoard'

export function LotdgMountStableScreen({ characterId, onStateChange }: LotdgMutableScreenProps) {
  const { translate } = useLotdgLocale()
  const [stable, setStable] = useState<LotdgStableBrowse | null>(null)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/stable/${characterId}/browse`, lotdgStableBrowseSchema)
      .then(setStable)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.COMMERCE, path, valueMap)

  const act = async (action: string, body: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(
        `/stable/${characterId}/${action}`,
        lotdgStableMutationSchema,
        body,
      )

      if (result.message_key !== undefined) {
        setMessage(resolveMessageKeyLabel(result.message_key, translate))
      } else if (result.bought === true) {
        setMessage(
          label('stable.bought', {
            mount: result.mount_name ?? '',
            gold: result.cost_gold ?? 0,
            gem: result.cost_gem ?? 0,
          }),
        )
      } else if (result.sold === true) {
        setMessage(
          label('stable.sold', {
            mount: result.mount_name ?? '',
            gold: result.resale_gold ?? 0,
            gem: result.resale_gem ?? 0,
          }),
        )
      }

      onStateChange()
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('stable.title')}</h2>

      {stable !== null && (
        <>
          <p>{label('stable.status', { gold: stable.gold, gem: stable.gem })}</p>

          {stable.current_mount !== null && (
            <p>
              {label('stable.current', {
                mount: stable.current_mount.mount_name,
                gold: stable.current_mount.resale_gold,
                gem: stable.current_mount.resale_gem,
              })}{' '}
              <button type="button" className="lotdg-button" onClick={() => void act('sell')}>
                {label('stable.action.sell')}
              </button>
            </p>
          )}

          <LotdgDataTable
            rowList={stable.mount_list}
            rowKey={(mount) => mount.mount_id}
            emptyText={label('stable.empty')}
            columnList={[
              {
                columnKey: 'name',
                headText: label('stable.column.name'),
                render: (mount) => mount.mount_name,
              },
              {
                columnKey: 'description',
                headText: label('stable.column.description'),
                render: (mount) => mount.mount_description,
              },
              {
                columnKey: 'cost',
                headText: label('stable.column.cost'),
                render: (mount) =>
                  label('stable.cost-value', { gold: mount.cost_gold, gem: mount.cost_gem }),
              },
              {
                columnKey: 'benefit',
                headText: label('stable.column.benefit'),
                render: (mount) =>
                  label('stable.benefit-value', {
                    forestFight: mount.extra_forest_fight,
                    tavern: mount.tavern_access_level,
                  }),
              },
              {
                columnKey: 'action',
                headText: label('stable.column.action'),
                render: (mount) => (
                  <button
                    type="button"
                    className="lotdg-button"
                    onClick={() => void act('buy', { mount_id: mount.mount_id })}
                  >
                    {label('stable.action.buy')}
                  </button>
                ),
              },
            ]}
          />
        </>
      )}

      <LotdgNoticeLine messageText={message} />

      <LotdgCommentaryBoard characterId={characterId} sectionCode="stables" />
    </section>
  )
}
