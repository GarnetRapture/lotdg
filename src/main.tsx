import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './style/lotdg-style-entry.css'
import { LotdgApp } from './app/LotdgApp'

const rootElement = document.getElementById('root')

if (rootElement === null) {
  throw new Error('마운트 지점 #root 를 찾을 수 없습니다.')
}

createRoot(rootElement).render(
  <StrictMode>
    <LotdgApp />
  </StrictMode>,
)
