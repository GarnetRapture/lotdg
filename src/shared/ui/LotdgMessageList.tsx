import type { LotdgMessageListProps } from '../type/lotdg-ui-component-contract'

export function LotdgMessageList({ messageTextList, colorClassName }: LotdgMessageListProps) {
  if (messageTextList.length === 0) {
    return null
  }

  return (
    <ul className={colorClassName}>
      {messageTextList.map((messageText) => (
        <li key={messageText}>{messageText}</li>
      ))}
    </ul>
  )
}
