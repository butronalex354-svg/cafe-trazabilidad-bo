import { boot } from 'quasar/wrappers'
import axios from 'axios'
import { getToken } from '@/utils/authStorage'

const api = axios.create({ baseURL: `http://${window.location.hostname}:8000/api` })

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default boot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
