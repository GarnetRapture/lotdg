import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgDarkHorseEnemySearchSchema,
  lotdgDarkHorseSchema,
  type LotdgDarkHorse,
  type LotdgDarkHorseEnemySearch,
} from '../../shared/schema/world/lotdg-special-event-schema'
import { lotdgCommentaryPostSchema } from '../../shared/schema/social/lotdg-commentary-schema'
import { resolveErrorLabel, resolveMessageKeyLabel } from '../../shared/lib/lotdg-error-label'
import { resolveEquipmentName } from '../../shared/lib/lotdg-equipment-name-resolver'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import {
  LOTDG_DARK_HORSE_ACTION_CODE,
  LOTDG_DARK_HORSE_DRAW_OUTCOME_CODE,
  LOTDG_DARK_HORSE_STAGE_CODE,
  LOTDG_DARK_HORSE_STONE_SIDE_CODE,
  LOTDG_DARK_HORSE_STONE_SIDE_CODE_LIST,
  type LotdgDarkHorseActionCode,
  type LotdgDarkHorseStoneSideCode,
} from '../../shared/constant/lotdg-special-event-code'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import { LOTDG_COMMENT_MAXIMUM_LENGTH } from '../../shared/constant/lotdg-commentary-section-code'
import type { LotdgDarkHorseScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgCommentLine,
  LotdgDataTable,
  LotdgFieldRow,
  LotdgForm,
  LotdgMarkupText,
  LotdgNoticeLine,
  LotdgScreen,
  LotdgSection,
  LotdgSelectField,
  LotdgSubmitButton,
  LotdgText,
  LotdgTextField,
} from '../../shared/ui'

const LOTDG_DECIMAL_RADIX = 10

const LOTDG_LIST_SEPARATOR = ', '

export function LotdgDarkHorseScreen({
  characterId,
  onStateChange,
  onLeave,
}: LotdgDarkHorseScreenProps) {
  const { translate } = useLotdgLocale()
  const [tavern, setTavern] = useState<LotdgDarkHorse | null>(null)
  const [enemySearch, setEnemySearch] = useState<LotdgDarkHorseEnemySearch | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [betText, setBetText] = useState('')
  const [stoneSide, setStoneSide] = useState<LotdgDarkHorseStoneSideCode>(
    LOTDG_DARK_HORSE_STONE_SIDE_CODE.LIKE_PAIR,
  )
  const [etchingText, setEtchingText] = useState('')
  const [message, setMessage] = useState('')

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.FOREST, path, valueMap)

  const characterStatLabel = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.CHARACTER_STAT, path, valueMap)

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

  const act = async (
    action: LotdgDarkHorseActionCode,
    body: Record<string, string | number> = {},
  ) => {
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

  const searchEnemy = async () => {
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

  const postEtching = async () => {
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
      void act(LOTDG_DARK_HORSE_ACTION_CODE.ETCHING)
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  const parsedBet = Number.parseInt(betText, LOTDG_DECIMAL_RADIX) || 0

  return (
    <LotdgScreen titleText={label('special.darkhorse.title')}>
      <LotdgMarkupText sourceText={label('special.darkhorse.description')} />

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.OUTSIDE && (
        <LotdgActionRow>
          <LotdgButton
            labelSlot={label('special.darkhorse.action.enter')}
            onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.ENTER)}
          />
          <LotdgButton labelSlot={label('special.action.return-to-forest')} onSelect={onLeave} />
        </LotdgActionRow>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.INSIDE && (
        <>
          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('special.darkhorse.action.etching')}
              onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.ETCHING)}
            />
            <LotdgButton
              labelSlot={label('special.darkhorse.action.leave')}
              onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.LEAVE)}
            />
          </LotdgActionRow>

          <LotdgSection titleSlot={label('special.darkhorse.dice.title')}>
            <LotdgText>{label('special.darkhorse.dice.rule')}</LotdgText>
            <LotdgFieldRow>
              <LotdgTextField value={betText} onValueChange={setBetText} isNumeric />
              <LotdgButton
                labelSlot={label('special.action.bet')}
                onSelect={() =>
                  void act(LOTDG_DARK_HORSE_ACTION_CODE.DICE_START, { bet: parsedBet })
                }
              />
            </LotdgFieldRow>
          </LotdgSection>

          <LotdgSection titleSlot={label('special.darkhorse.stone.title')}>
            <LotdgText>{label('special.darkhorse.stone.rule')}</LotdgText>
            <LotdgFieldRow>
              <LotdgSelectField
                value={stoneSide}
                onValueChange={(nextValue) =>
                  setStoneSide(nextValue as LotdgDarkHorseStoneSideCode)
                }
                optionList={LOTDG_DARK_HORSE_STONE_SIDE_CODE_LIST.map((side) => ({
                  optionValue: side,
                  labelText: label(`special.darkhorse.stone.side.${side}`),
                }))}
              />
              <LotdgButton
                labelSlot={label('special.action.bet')}
                onSelect={() =>
                  void act(LOTDG_DARK_HORSE_ACTION_CODE.STONE_START, {
                    side: stoneSide,
                    bet: parsedBet,
                  })
                }
              />
            </LotdgFieldRow>
          </LotdgSection>

          <LotdgSection titleSlot={label('special.darkhorse.enemy.title')}>
            <LotdgForm onSubmit={() => void searchEnemy()}>
              <LotdgText>{label('special.darkhorse.enemy.rule')}</LotdgText>
              <LotdgFieldRow>
                <LotdgTextField value={searchTerm} onValueChange={setSearchTerm} />
                <LotdgSubmitButton labelSlot={label('special.action.search')} />
              </LotdgFieldRow>
            </LotdgForm>
          </LotdgSection>

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
                    <LotdgButton
                      labelSlot={label('special.darkhorse.enemy.action.inspect', {
                        cost: enemySearch.lookup_cost,
                      })}
                      onSelect={() =>
                        void act(LOTDG_DARK_HORSE_ACTION_CODE.ENEMY_INSPECT, {
                          target_login_name: candidate.login_name,
                        })
                      }
                    />
                  ),
                },
              ]}
            />
          )}
        </>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.DICE_GAME && (
        <LotdgActionRow>
          <LotdgText>
            {label('special.darkhorse.dice.progress', {
              roll: tavern.roll ?? 0,
              count: tavern.roll_count ?? 0,
              bet: tavern.bet ?? 0,
            })}
          </LotdgText>
          <LotdgButton
            labelSlot={label('special.darkhorse.dice.action.reroll')}
            isDisabled={tavern.can_reroll !== true}
            onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.DICE_REROLL)}
          />
          <LotdgButton
            labelSlot={label('special.darkhorse.dice.action.keep')}
            onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.DICE_KEEP)}
          />
        </LotdgActionRow>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.DICE_SETTLED && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
          {label(
            `special.darkhorse.dice.outcome.${tavern.outcome ?? LOTDG_DARK_HORSE_DRAW_OUTCOME_CODE}`,
            {
              playerRoll: tavern.player_roll ?? 0,
              oldManRoll: (tavern.old_man_roll_list ?? []).join(LOTDG_LIST_SEPARATOR),
              gold: tavern.gold_gained ?? tavern.gold_lost ?? 0,
            },
          )}
        </LotdgText>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.STONE_GAME && (
        <LotdgActionRow>
          <LotdgText>
            {label('special.darkhorse.stone.progress', {
              drawn: (tavern.drawn_list ?? [])
                .map((stone) => label(`special.darkhorse.stone.${stone}`))
                .join(LOTDG_LIST_SEPARATOR),
              playerScore: tavern.player_score ?? 0,
              oldManScore: tavern.old_man_score ?? 0,
              red: tavern.red ?? 0,
              blue: tavern.blue ?? 0,
              bet: tavern.bet ?? 0,
            })}
          </LotdgText>
          <LotdgButton
            labelSlot={label('special.darkhorse.stone.action.draw')}
            onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.STONE_DRAW)}
          />
        </LotdgActionRow>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.STONE_SETTLED && (
        <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.LIGHT_GREEN}>
          {label(
            `special.darkhorse.stone.outcome.${tavern.outcome ?? LOTDG_DARK_HORSE_DRAW_OUTCOME_CODE}`,
            {
              playerScore: tavern.player_score ?? 0,
              oldManScore: tavern.old_man_score ?? 0,
              gold: tavern.gold_gained ?? tavern.gold_lost ?? 0,
            },
          )}
        </LotdgText>
      )}

      {tavern?.inspected === true && (
        <LotdgText>
          {label('special.darkhorse.enemy.result', {
            name: tavern.display_name ?? '',
            level: tavern.level ?? 0,
            hitPoint: tavern.max_hit_point ?? 0,
            gold: tavern.gold ?? 0,
            weapon: resolveEquipmentName(tavern.weapon_name ?? '', characterStatLabel),
            armor: resolveEquipmentName(tavern.armor_name ?? '', characterStatLabel),
            attack: tavern.attack_point ?? 0,
            defence: tavern.defence_point ?? 0,
          })}
        </LotdgText>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.ETCHING && (
        <>
          {(tavern.comment_list ?? []).map((entry) => (
            <LotdgCommentLine
              key={entry.commentary_id}
              authorName={entry.display_name}
              commentText={entry.comment_text}
            />
          ))}

          <LotdgForm onSubmit={() => void postEtching()}>
            <LotdgFieldRow>
              <LotdgTextField
                value={etchingText}
                onValueChange={setEtchingText}
                maximumLength={LOTDG_COMMENT_MAXIMUM_LENGTH}
              />
              <LotdgSubmitButton labelSlot={label('special.action.post')} />
              <LotdgButton
                labelSlot={label('special.darkhorse.action.back')}
                onSelect={() => void act(LOTDG_DARK_HORSE_ACTION_CODE.ENTER)}
              />
            </LotdgFieldRow>
          </LotdgForm>
        </>
      )}

      {tavern?.stage === LOTDG_DARK_HORSE_STAGE_CODE.LEFT && (
        <LotdgActionRow>
          <LotdgButton labelSlot={label('special.action.return-to-forest')} onSelect={onLeave} />
        </LotdgActionRow>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
