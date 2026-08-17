import type { FormEvent } from 'react'
import type { LotdgFormProps } from '../type/lotdg-ui-component-contract'

export function LotdgForm({ onSubmit, children }: LotdgFormProps) {
  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    onSubmit()
  }

  return <form onSubmit={handleSubmit}>{children}</form>
}
