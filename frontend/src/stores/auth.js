import { defineStore } from 'pinia'
import { api } from '@/boot/axios'
import { getToken, getUser, setAuth, clearAuth } from '@/utils/authStorage'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: getToken(),
    user: getUser()
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    rol: (state) => state.user?.rol || null
  },

  actions: {
    async login (email, password, remember = true) {
      const { data } = await api.post('/login', { email, password })
      this.token = data.token
      this.user = data.user
      setAuth(data.token, data.user, remember)
      return data.user
    },

    async register (nombre, email, password, passwordConfirmation, datosProductor = {}) {
      const { data } = await api.post('/register', {
        nombre,
        email,
        password,
        password_confirmation: passwordConfirmation,
        ci: datosProductor.ci,
        telefono: datosProductor.telefono,
        whatsapp: datosProductor.whatsapp,
        direccion: datosProductor.direccion
      })
      this.token = data.token
      this.user = data.user
      setAuth(data.token, data.user, true)
      return data.user
    },

    async logout () {
      try {
        await api.post('/logout')
      } catch {
        // si el token ya no es valido, igual limpiamos localmente
      }
      this.token = null
      this.user = null
      clearAuth()
    }
  }
})
