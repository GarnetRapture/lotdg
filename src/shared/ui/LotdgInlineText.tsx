import type { LotdgInlineTextProps } from '../type/lotdg-ui-component-contract'

export function LotdgInlineText({ children, colorClassName }: LotdgInlineTextProps) {
  return <span className={colorClassName}>{children}</span>
}
