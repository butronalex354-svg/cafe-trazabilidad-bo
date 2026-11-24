// Calculos simples sobre el borde de una parcela ([[lat, lng], ...]).
// Para terrenos de pocas hectareas basta con proyectar a metros alrededor
// de la latitud media (el error es muy pequeño a esa escala).
const RADIO_TIERRA = 6371008.8 // metros

export function bordeValido (puntos) {
  return Array.isArray(puntos) && puntos.length >= 3 &&
    puntos.every(p => Array.isArray(p) && p.length === 2 && p.every(n => Number.isFinite(Number(n))))
}

export function areaHectareas (puntos) {
  if (!bordeValido(puntos)) return 0
  const latMedia = puntos.reduce((s, p) => s + Number(p[0]), 0) / puntos.length
  const k = Math.PI / 180
  const xy = puntos.map(([lat, lng]) => [
    RADIO_TIERRA * Number(lng) * k * Math.cos(latMedia * k),
    RADIO_TIERRA * Number(lat) * k
  ])
  let doble = 0
  for (let i = 0; i < xy.length; i++) {
    const [x1, y1] = xy[i]
    const [x2, y2] = xy[(i + 1) % xy.length]
    doble += x1 * y2 - x2 * y1
  }
  return Math.abs(doble) / 2 / 10000
}

export function centro (puntos) {
  if (!bordeValido(puntos)) return null
  const lat = puntos.reduce((s, p) => s + Number(p[0]), 0) / puntos.length
  const lng = puntos.reduce((s, p) => s + Number(p[1]), 0) / puntos.length
  return { lat, lng }
}

export function formatoHa (ha) {
  return Number(ha).toLocaleString('es-BO', { maximumFractionDigits: 2 })
}
