import {
  LOTDG_LOCALE_NAMESPACE,
  type LotdgLocaleNamespace,
} from '../constant/lotdg-supported-locale'

const NEWS_LABEL_PATTERN = /(?:[a-z-]+\.)*[a-z-]+\.news\.[a-z-]+/g

export function resolveNewsText(
  newsText: string,
  translate: (
    namespace: LotdgLocaleNamespace,
    labelPath: string,
    valueMap?: Record<string, string | number>,
  ) => string,
): string {
  return newsText.replace(NEWS_LABEL_PATTERN, (labelPath) =>
    translate(LOTDG_LOCALE_NAMESPACE.SOCIAL, labelPath),
  )
}
