import { LOTDG_DEFAULT_EQUIPMENT_LABEL_PATH } from '../constant/lotdg-legacy-code'
import type { LotdgLabelFunction } from '../type/lotdg-screen-contract'

export function resolveEquipmentName(equipmentName: string, label: LotdgLabelFunction): string {
  const labelPath = LOTDG_DEFAULT_EQUIPMENT_LABEL_PATH[equipmentName]

  return labelPath === undefined ? equipmentName : label(labelPath)
}
