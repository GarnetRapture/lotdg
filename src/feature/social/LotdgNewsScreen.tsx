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
import { LotdgNoticeLine } from '../../shared/ui/LotdgNoticeLine'

export function LotdgNewsScreen({
  characterId,
  canRemove,
}: {
  readonly characterId: number | null
  readonly canRemove: boolean
}) {
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
        action: 'remove',
        character_id: characterId,
        news_id: newsId,
      })
      reload()
    } catch (error) {
      setMessage(resolveErrorLabel(error, translate))
    }
  }

  return (
    <section>
      <h2>{label('news.title')}</h2>

      {news !== null && (
        <>
          <p>
            {label('news.date', { date: news.news_date })}
            {news.total_count > news.news_list.length &&
              ` ${label('news.range', {
                from: news.range_from,
                to: news.range_to,
                total: news.total_count,
              })}`}
          </p>

          <p>
            <button
              type="button"
              className="lotdg-button"
              onClick={() => {
                setDayOffset((previous) => previous + 1)
                setPage(1)
              }}
            >
              {label('news.action.previous-day')}
            </button>{' '}
            <button
              type="button"
              className="lotdg-button"
              disabled={dayOffset === 0}
              onClick={() => {
                setDayOffset((previous) => Math.max(0, previous - 1))
                setPage(1)
              }}
            >
              {label('news.action.next-day')}
            </button>
          </p>

          {news.news_list.length === 0 ? (
            <p className="colDkWhite">{label('news.empty')}</p>
          ) : (
            news.news_list.map((item) => (
              <p key={item.news_id}>
                {canRemove && (
                  <button
                    type="button"
                    className="lotdg-button"
                    onClick={() => void remove(item.news_id)}
                  >
                    {label('news.action.remove')}
                  </button>
                )}{' '}
                {parseLegacyMarkup(resolveNewsText(item.news_text, translate))}
              </p>
            ))
          )}

          {news.page_count > 1 && (
            <p>
              {Array.from({ length: news.page_count }, (_unused, pageIndex) => (
                <button
                  key={pageIndex + 1}
                  type="button"
                  className="lotdg-button"
                  disabled={news.page === pageIndex + 1}
                  onClick={() => setPage(pageIndex + 1)}
                >
                  {pageIndex + 1}
                </button>
              ))}
            </p>
          )}
        </>
      )}

      <LotdgNoticeLine messageText={message} />
    </section>
  )
}
