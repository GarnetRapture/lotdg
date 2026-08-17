export type LotdgDurationPart = {
  readonly hour: number
  readonly minute: number
  readonly second: number
}

export function splitDuration(totalSecond: number): LotdgDurationPart {
  const clamped = Math.max(0, Math.trunc(totalSecond))

  return {
    hour: Math.floor(clamped / 3600),
    minute: Math.floor((clamped % 3600) / 60),
    second: clamped % 60,
  }
}
