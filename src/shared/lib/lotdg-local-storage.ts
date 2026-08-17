export function readLocalStorageText(storageKey: string): string | null {
  try {
    return window.localStorage.getItem(storageKey)
  } catch {
    return null
  }
}

export function writeLocalStorageText(storageKey: string, storageValue: string): void {
  try {
    window.localStorage.setItem(storageKey, storageValue)
  } catch {
    return
  }
}
