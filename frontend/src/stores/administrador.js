import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Panel del Administrador: aprobacion de cuentas (RF25) y reportes
// generales de supervision del sistema.
export const useAdministradorStore = defineStore('administrador', {
  state: () => ({
    usuarios: [],
    reportes: null,
    cargando: false
  }),

  getters: {
    cuentasPendientes: (state) => state.usuarios.filter(u => u.estado === 'pendiente')
  },

  actions: {
    async cargarUsuarios ({ rol = null, estado = null } = {}) {
      this.cargando = true
      try {
        const params = {}
        if (rol) params.rol = rol
        if (estado) params.estado = estado
        const { data } = await api.get('/administrador/usuarios', { params })
        this.usuarios = data
      } finally {
        this.cargando = false
      }
    },

    async aprobarUsuario (id) {
      const { data } = await api.put(`/administrador/usuarios/${id}/aprobar`)
      const indice = this.usuarios.findIndex(u => u.id === id)
      if (indice !== -1) this.usuarios[indice] = data.usuario
    },

    async rechazarUsuario (id) {
      const { data } = await api.put(`/administrador/usuarios/${id}/rechazar`)
      const indice = this.usuarios.findIndex(u => u.id === id)
      if (indice !== -1) this.usuarios[indice] = data.usuario
    },

    async crearUsuario (datos) {
      const { data } = await api.post('/administrador/usuarios', datos)
      this.usuarios.unshift(data.usuario)
      return data.usuario
    },

    async desactivarUsuario (id) {
      const { data } = await api.put(`/administrador/usuarios/${id}/desactivar`)
      const indice = this.usuarios.findIndex(u => u.id === id)
      if (indice !== -1) this.usuarios[indice] = data.usuario
    },

    async reactivarUsuario (id) {
      const { data } = await api.put(`/administrador/usuarios/${id}/reactivar`)
      const indice = this.usuarios.findIndex(u => u.id === id)
      if (indice !== -1) this.usuarios[indice] = data.usuario
    },

    async cargarReportes () {
      const { data } = await api.get('/administrador/reportes')
      this.reportes = data
    }
  }
})
