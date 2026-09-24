import { defineStore } from 'pinia'
import { api } from '@/boot/axios'
import { getToken, getUser, setAuth, clearAuth, updateStoredUser } from '@/utils/authStorage'

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
      // La cuenta queda "pendiente" hasta que el Administrador la aprueba,
      // asi que el registro ya NO entrega token ni inicia sesion sola.
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
      return data.message
    },

    async actualizarCuenta (nombre, email) {
      const { data } = await api.put('/me', { nombre, email })
      this.user = data.user
      updateStoredUser(data.user)
      return data.user
    },

    async cambiarPassword (passwordActual, passwordNueva, passwordNuevaConfirmation) {
      const { data } = await api.put('/me/password', {
        password_actual: passwordActual,
        password_nueva: passwordNueva,
        password_nueva_confirmation: passwordNuevaConfirmation
      })
      return data.message
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
