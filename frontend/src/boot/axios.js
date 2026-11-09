import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { getToken, clearAuth } from '@/utils/authStorage'
import { BACKEND_URL } from '@/utils/backend'

const api = axios.create({ baseURL: `${BACKEND_URL}/api` })

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Si el backend responde 401 a una peticion que SI llevaba token, la sesion ya
// no sirve (token vencido, o el Administrador desactivo la cuenta): se limpia y
// se vuelve al login con un aviso. El 401 de /login con contraseña incorrecta no
// entra aca porque esa peticion no lleva token.
let cerrandoSesion = false
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const llevabaToken = !!error.config?.headers?.Authorization
    if (error.response?.status === 401 && llevabaToken && !cerrandoSesion) {
      cerrandoSesion = true
      clearAuth()
      window.location.hash = '#/login?sesion=cerrada'
      window.location.reload()
    }
    return Promise.reject(error)
  }
)

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
