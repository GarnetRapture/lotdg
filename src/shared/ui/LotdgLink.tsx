import type { LotdgLinkProps } from '../type/lotdg-ui-component-contract'

export function LotdgLink({ hashCode, labelSlot, onSelect }: LotdgLinkProps) {
  return (
    <a
      href={`#${hashCode}`}
      onClick={(event) => {
        event.preventDefault()
        onSelect()
      }}
    >
      {labelSlot}
    </a>
  )
}
