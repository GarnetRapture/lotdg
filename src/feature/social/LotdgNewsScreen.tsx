import { useCallback, useEffect, useState } from 'react'
import { getJson, postForm } from '../../shared/lib/lotdg-api-client'
import {
  lotdgNewsListSchema,
  lotdgNewsRemovalSchema,
  type LotdgNewsList,
} from '../../shared/schema/system/lotdg-api-response-schema'
import { parseLegacyMarkup } from '../../shared/lib/lotdg-legacy-markup-parser'
import { resolveNewsText } from '../../shared/lib/lotdg-news-label-resolver'
import { resolveErrorLabel } from '../../shared/lib/lotdg-error-label'
import { useLotdgLocale } from '../../i18n/useLotdgLocale'
import { LOTDG_LOCALE_NAMESPACE } from '../../shared/constant/lotdg-supported-locale'
import { LOTDG_TEXT_COLOR_CLASS_NAME } from '../../shared/constant/lotdg-legacy-color-code'
import type { LotdgNewsScreenProps } from '../../shared/type/lotdg-screen-contract'
import {
  LotdgActionRow,
  LotdgButton,
  LotdgNoticeLine,
  LotdgPaginationRow,
  LotdgScreen,
  LotdgText,
} from '../../shared/ui'

const LOTDG_NEWS_ACTION_CODE = { REMOVE: 'remove' } as const

const LOTDG_NEWS_FIRST_PAGE = 1

export function LotdgNewsScreen({ characterId, canRemove }: LotdgNewsScreenProps) {
  const { translate } = useLotdgLocale()
  const [news, setNews] = useState<LotdgNewsList | null>(null)
  const [dayOffset, setDayOffset] = useState(0)
  const [page, setPage] = useState(1)
  const [message, setMessage] = useState('')

  const reload = useCallback(() => {
    getJson(`/news?day_offset=${dayOffset}&page=${page}`, lotdgNewsListSchema)
      .then(setNews)
      .catch((error: unknown) => {
        setMessage(resolveErrorLabel(error, translate))
      })
  }, [dayOffset, page, translate])

  useEffect(() => {
    reload()
  }, [reload])

  const label = (path: string, valueMap?: Record<string, string | number>) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, path, valueMap)

  const remove = async (newsId: number) => {
    if (characterId === null) {
      return
    }

    try {
      await postForm('/news', lotdgNewsRemovalSchema, {
        action: LOTDG_NEWS_ACTION_CODE.REMOVE,
        character_id: characterId,
        news_id: newsId,
      })
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <LotdgScreen titleText={label('news.title')}>
      {news !== null && (
        <>
          <LotdgText>
            {label('news.date', { date: news.news_date })}
            {news.total_count > news.news_list.length &&
              ` ${label('news.range', {
                from: news.range_from,
                to: news.range_to,
                total: news.total_count,
              })}`}
          </LotdgText>

          <LotdgActionRow>
            <LotdgButton
              labelSlot={label('news.action.previous-day')}
              onSelect={() => {
                setDayOffset((previous) => previous + 1)
                setPage(LOTDG_NEWS_FIRST_PAGE)
              }}
            />
            <LotdgButton
              labelSlot={label('news.action.next-day')}
              isDisabled={dayOffset === 0}
              onSelect={() => {
                setDayOffset((previous) => Math.max(0, previous - 1))
                setPage(LOTDG_NEWS_FIRST_PAGE)
              }}
            />
          </LotdgActionRow>

          {news.news_list.length === 0 ? (
            <LotdgText colorClassName={LOTDG_TEXT_COLOR_CLASS_NAME.DARK_WHITE}>
              {label('news.empty')}
            </LotdgText>
          ) : (
            news.news_list.map((item) => (
              <LotdgActionRow key={item.news_id}>
                {canRemove && (
                  <LotdgButton
                    labelSlot={label('news.action.remove')}
                    onSelect={() => void remove(item.news_id)}
                  />
                )}
                <LotdgText>
                  {parseLegacyMarkup(resolveNewsText(item.news_text, translate))}
                </LotdgText>
              </LotdgActionRow>
            ))
          )}

          <LotdgPaginationRow
            pageCount={news.page_count}
            activePageIndex={news.page - LOTDG_NEWS_FIRST_PAGE}
            pageLabelText={(pageNumber) => String(pageNumber)}
            onPageSelect={(pageIndex) => setPage(pageIndex + LOTDG_NEWS_FIRST_PAGE)}
          />
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </LotdgScreen>
  )
}
