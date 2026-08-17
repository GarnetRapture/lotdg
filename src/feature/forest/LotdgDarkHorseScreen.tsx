import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgDarkHorseEnemySearchSchema,
  lotdgDarkHorseSchema,
  type LotdgDarkHorse,
  type LotdgDarkHorseEnemySearch,
} from '../../shared/schema/world/lotdg-special-event-schema'
import { lotdgCommentaryPostSchema } from '../../shared/schema/social/lotdg-commentary-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LotdgDataTable } from '../../shared/ui/LotdgDataTable'
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'
import type { LotdgMutableScreenProps } from '../../shared/type/lotdg-screen-contract'

const STONE_SIDE_LIST = ['likepair', 'unlikepair'] as const

export function LotdgDarkHorseScreen({
  characterId,
  onStateChange,
  onLeave,
}: LotdgMutableScreenProps & { readonly onLeave: () => void }) {
  const { translate } = useLotdgLocale()
  const [tavern, setTavern] = useState<LotdgDarkHorse | null>(null)
  const [enemySearch, setEnemySearch] = useState<LotdgDarkHorseEnemySearch | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [betText, setBetText] = useState('')
  const [stoneSide, setStoneSide] = useState<(typeof STONE_SIDE_LIST)[number]>('likepair')
  const [etchingText, setEtchingText] = useState('')
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.FOREST, path, valueMap)

  const start = useCallback(() => {
    getJson(`/special/darkhorse/${characterId}/start`, lotdgDarkHorseSchema)
      .then(setTavern)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [characterId, translate])

  useEffect(() => {
    start()
  }, [start])

  const act = async (action: string, body: Record<string, string | number> = {}) => {
    try {
      const result = await postForm(
        `/special/darkhorse/${characterId}/${action}`,
        lotdgDarkHorseSchema,
        body,
      )

      setTavern(result)
      setMessage(
        result.message_key === undefined
          ? ''
          : resolveMessageKeyLabel(result.message_key, translate),
      )
      onStateChange()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const searchEnemy = async (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()

    if (searchTerm.trim() === '') {
      return
    }

    try {
      setEnemySearch(
        await getJson(
          `/special/darkhorse/${characterId}/enemy-search?search_term=${encodeURIComponent(searchTerm.trim())}`,
          lotdgDarkHorseEnemySearchSchema,
        ),
      )
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const postEtching = async (submitEvent: FormEvent<HTMLFormElement>) => {
    submitEvent.preventDefault()

    if (etchingText.trim() === '') {
      return
    }

    try {
      const result = await postForm(
        `/special/darkhorse/${characterId}/etching-post`,
        lotdgCommentaryPostSchema,
        { comment_text: etchingText },
      )

      setMessage(result.posted ? '' : resolveMessageKeyLabel(result.message_key, translate))
      setEtchingText('')
      void act('etching')
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const parsedBet = Number.parseInt(betText, 10) || 0

  return (
    <section>
      <h2>{label('special.darkhorse.title')}</h2>

      <p>{parseLegacyMarkup(label('special.darkhorse.description'))}</p>

      {tavern?.stage === 'outside' && (
        <p>
          <button type="button" className="lotdg-button" onClick={() => void act('enter')}>
            {label('special.darkhorse.action.enter')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={onLeave}>
            {label('special.action.return-to-forest')}
          </button>
        </p>
      )}

      {tavern?.stage === 'inside' && (
        <>
          <p>
            <button type="button" className="lotdg-button" onClick={() => void act('etching')}>
              {label('special.darkhorse.action.etching')}
            </button>{' '}
            <button type="button" className="lotdg-button" onClick={() => void act('leave')}>
              {label('special.darkhorse.action.leave')}
            </button>
          </p>

          <h3>{label('special.darkhorse.dice.title')}</h3>
          <p>
            {label('special.darkhorse.dice.rule')}{' '}
            <input
              className="lotdg-input"
              inputMode="numeric"
              value={betText}
              onChange={(changeEvent) => setBetText(changeEvent.target.value)}
            />{' '}
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('dice-start', { bet: parsedBet })}
            >
              {label('special.action.bet')}
            </button>
          </p>

          <h3>{label('special.darkhorse.stone.title')}</h3>
          <p>
            {label('special.darkhorse.stone.rule')}{' '}
            <select
              className="lotdg-select"
              value={stoneSide}
              onChange={(changeEvent) =>
                setStoneSide(changeEvent.target.value as (typeof STONE_SIDE_LIST)[number])
              }
            >
              {STONE_SIDE_LIST.map((side) => (
                <option key={side} value={side}>
                  {label(`special.darkhorse.stone.side.${side}`)}
                </option>
              ))}
            </select>{' '}
            <button
              type="button"
              className="lotdg-button"
              onClick={() => void act('stone-start', { side: stoneSide, bet: parsedBet })}
            >
              {label('special.action.bet')}
            </button>
          </p>

          <h3>{label('special.darkhorse.enemy.title')}</h3>
          <form onSubmit={(submitEvent) => void searchEnemy(submitEvent)}>
            <p>
              {label('special.darkhorse.enemy.rule')}{' '}
              <input
                className="lotdg-input"
                value={searchTerm}
                onChange={(changeEvent) => setSearchTerm(changeEvent.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('special.action.search')}
              </button>
            </p>
          </form>

          {enemySearch !== null && (
            <LotdgDataTable
              rowList={enemySearch.candidate_list}
              rowKey={(candidate) => candidate.login_name}
              emptyText={label('special.darkhorse.enemy.empty')}
              columnList={[
                {
                  columnKey: 'name',
                  headText: label('special.darkhorse.enemy.column.name'),
                  render: (candidate) => candidate.display_name,
                },
                {
                  columnKey: 'level',
                  headText: label('special.darkhorse.enemy.column.level'),
                  render: (candidate) => candidate.level,
                },
                {
                  columnKey: 'action',
                  headText: label('special.darkhorse.enemy.column.action'),
                  render: (candidate) => (
                    <button
                      type="button"
                      className="lotdg-button"
                      onClick={() =>
                        void act('enemy-inspect', { target_login_name: candidate.login_name })
                      }
                    >
                      {label('special.darkhorse.enemy.action.inspect', {
                        cost: enemySearch.lookup_cost,
                      })}
                    </button>
                  ),
                },
              ]}
            />
          )}
        </>
      )}

      {tavern?.stage === 'dice-game' && (
        <p>
          {label('special.darkhorse.dice.progress', {
            roll: tavern.roll ?? 0,
            count: tavern.roll_count ?? 0,
            bet: tavern.bet ?? 0,
          })}{' '}
          <button
            type="button"
            className="lotdg-button"
            disabled={tavern.can_reroll !== true}
            onClick={() => void act('dice-reroll')}
          >
            {label('special.darkhorse.dice.action.reroll')}
          </button>{' '}
          <button type="button" className="lotdg-button" onClick={() => void act('dice-keep')}>
            {label('special.darkhorse.dice.action.keep')}
          </button>
        </p>
      )}

      {tavern?.stage === 'dice-settled' && (
        <p className="colLtGreen">
          {label(`special.darkhorse.dice.outcome.${tavern.outcome ?? 'draw'}`, {
            playerRoll: tavern.player_roll ?? 0,
            oldManRoll: (tavern.old_man_roll_list ?? []).join(', '),
            gold: tavern.gold_gained ?? tavern.gold_lost ?? 0,
          })}
        </p>
      )}

      {tavern?.stage === 'stone-game' && (
        <p>
          {label('special.darkhorse.stone.progress', {
            drawn: (tavern.drawn_list ?? [])
              .map((stone) => label(`special.darkhorse.stone.${stone}`))
              .join(', '),
            playerScore: tavern.player_score ?? 0,
            oldManScore: tavern.old_man_score ?? 0,
            red: tavern.red ?? 0,
            blue: tavern.blue ?? 0,
            bet: tavern.bet ?? 0,
          })}{' '}
          <button type="button" className="lotdg-button" onClick={() => void act('stone-draw')}>
            {label('special.darkhorse.stone.action.draw')}
          </button>
        </p>
      )}

      {tavern?.stage === 'stone-settled' && (
        <p className="colLtGreen">
          {label(`special.darkhorse.stone.outcome.${tavern.outcome ?? 'draw'}`, {
            playerScore: tavern.player_score ?? 0,
            oldManScore: tavern.old_man_score ?? 0,
            gold: tavern.gold_gained ?? tavern.gold_lost ?? 0,
          })}
        </p>
      )}

      {tavern?.inspected === true && (
        <p>
          {label('special.darkhorse.enemy.result', {
            name: tavern.display_name ?? '',
            level: tavern.level ?? 0,
            hitPoint: tavern.max_hit_point ?? 0,
            gold: tavern.gold ?? 0,
            weapon: tavern.weapon_name ?? '',
            armor: tavern.armor_name ?? '',
            attack: tavern.attack_point ?? 0,
            defence: tavern.defence_point ?? 0,
          })}
        </p>
      )}

      {tavern?.stage === 'etching' && (
        <>
          {(tavern.comment_list ?? []).map((entry) => (
            <p key={entry.commentary_id}>
              <span className="colLtWhite">{entry.display_name}</span>{' '}
              {parseLegacyMarkup(entry.comment_text)}
            </p>
          ))}

          <form onSubmit={(submitEvent) => void postEtching(submitEvent)}>
            <p>
              <input
                className="lotdg-input"
                value={etchingText}
                maxLength={200}
                onChange={(changeEvent) => setEtchingText(changeEvent.target.value)}
              />{' '}
              <button type="submit" className="lotdg-button">
                {label('special.action.post')}
              </button>{' '}
              <button type="button" className="lotdg-button" onClick={() => void act('enter')}>
                {label('special.darkhorse.action.back')}
              </button>
            </p>
          </form>
        </>
      )}

      {tavern?.stage === 'left' && (
        <p>
          <button type="button" className="lotdg-button" onClick={onLeave}>
            {label('special.action.return-to-forest')}
          </button>
        </p>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
