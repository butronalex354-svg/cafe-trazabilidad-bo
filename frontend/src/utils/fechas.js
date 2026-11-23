// Las fechas sin hora llegan del servidor como texto ('2026-11-10', o
// '2026-11-10T00:00:00.000000Z' cuando el modelo las convierte a fecha).
// new Date('2026-11-10') las toma como medianoche en UTC, y en Bolivia (UTC-4)
// eso cae en el dia anterior: la cosecha del 10 se mostraba como del 9.
// aFecha() arma esas fechas en la hora local para que se vea el dia correcto.
// Las fechas con hora real (creado, solicitado, etc.) se dejan como estan.
const SOLO_FECHA = /^(\d{4})-(\d{2})-(\d{2})(T00:00:00(\.0+)?Z)?$/

export function aFecha (valor) {
  const m = typeof valor === 'string' ? valor.match(SOLO_FECHA) : null
  if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  return new Date(valor)
}

// Fecha de hoy en formato AAAA-MM-DD segun la hora local
// (toISOString() usa UTC y despues de las 20:00 en Bolivia ya da el dia siguiente).
export function hoyISO () {
  const f = new Date()
  return `${f.getFullYear()}-${String(f.getMonth() + 1).padStart(2, '0')}-${String(f.getDate()).padStart(2, '0')}`
}
