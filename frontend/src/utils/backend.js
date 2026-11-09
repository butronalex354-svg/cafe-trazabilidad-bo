// Direccion del backend (Laravel).
// - En la nube: viene de la variable API_URL (Railway), que se fija al compilar
//   el frontend (ver build.defineEnv en quasar.config.js). Ej: https://backend.up.railway.app
// - En la compu: el backend local en el puerto 8000 (igual que siempre), asi
//   tambien funciona desde el celular en la misma wifi.
export const BACKEND_URL = (import.meta.env.API_URL || `http://${window.location.hostname}:8000`).replace(/\/+$/, '')

// Las fotos llegan del backend como ruta relativa ("/storage/..."):
// se les antepone la direccion del backend.
export function urlDelBackend (rutaRelativa) {
  return `${BACKEND_URL}${rutaRelativa}`
}
