// El token/usuario se guarda en localStorage (persiste tras cerrar el navegador)
// si "recordarme" esta marcado, o en sessionStorage (se borra al cerrar el navegador)
// si no lo esta. Estas funciones centralizan esa logica para que el store, el
// router y el interceptor de axios lean siempre del mismo lugar.

export function getToken () {
  return localStorage.getItem('token') || sessionStorage.getItem('token')
}

export function getUser () {
  const raw = localStorage.getItem('user') || sessionStorage.getItem('user')
  return JSON.parse(raw || 'null')
}

export function setAuth (token, user, remember) {
  const target = remember ? localStorage : sessionStorage
  const other = remember ? sessionStorage : localStorage
  target.setItem('token', token)
  target.setItem('user', JSON.stringify(user))
  other.removeItem('token')
  other.removeItem('user')
}

// Actualiza el usuario guardado (ej. tras editar nombre/email en "Mi cuenta"),
// sin tener que saber si se guardo con "recordarme" (localStorage) o no
// (sessionStorage) - se actualiza el que efectivamente tenga el token.
export function updateStoredUser (user) {
  const target = localStorage.getItem('token') ? localStorage : sessionStorage
  target.setItem('user', JSON.stringify(user))
}

export function clearAuth () {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  sessionStorage.removeItem('token')
  sessionStorage.removeItem('user')
}
