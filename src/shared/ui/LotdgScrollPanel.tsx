import {
  LOTDG_PANEL_BODY_CLASS_NAME,
  LOTDG_PANEL_ROOT_CLASS_NAME,
  LOTDG_UI_CLASS_NAME,
} from '../constant/lotdg-ui-class-name'
import { LOTDG_LEGACY_ASSET_SOURCE } from '../constant/lotdg-legacy-asset-source'
import type { LotdgScrollPanelProps } from '../type/lotdg-ui-component-contract'

export function LotdgScrollPanel({ variantCode, children }: LotdgScrollPanelProps) {
  return (
    <div className={LOTDG_PANEL_ROOT_CLASS_NAME[variantCode]}>
      <img
        className={LOTDG_UI_CLASS_NAME.PANEL_CAP}
        src={LOTDG_LEGACY_ASSET_SOURCE.SCROLL_CAP_UPPER}
        alt=""
      />
      <div className={LOTDG_PANEL_BODY_CLASS_NAME[variantCode]}>{children}</div>
      <img
        className={LOTDG_UI_CLASS_NAME.PANEL_CAP}
        src={LOTDG_LEGACY_ASSET_SOURCE.SCROLL_CAP_LOWER}
        alt=""
      />
    </div>
  )
}
