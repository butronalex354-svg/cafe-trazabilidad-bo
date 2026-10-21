import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 6 - Verificación (RF14-17): bandeja de solicitudes del Verificador,
// revisión de evidencia del lote y cambio de estado.
export const useVerificacionStore = defineStore('verificacion', {
  state: () => ({
    solicitudes: [],
    detalle: null,
    cargando: false,
    // Conteo de pendientes para el globito del menu: independiente de
    // "solicitudes", que cambia segun el filtro (Todas/Observados/etc) y por
    // eso no sirve para esto cuando no se esta viendo justo "Pendientes".
    pendientes: 0
  }),

  actions: {
    async cargarSolicitudes (estado = null) {
      this.cargando = true
      try {
        const { data } = await api.get('/verificador/solicitudes', { params: estado ? { estado } : {} })
        this.solicitudes = data
      } finally {
        this.cargando = false
      }
    },

    async cargarContadorPendientes () {
      const { data } = await api.get('/verificador/solicitudes', { params: { estado: 'pendiente' } })
      this.pendientes = data.length
    },

    async cargarDetalle (id) {
      const { data } = await api.get(`/verificador/solicitudes/${id}`)
      this.detalle = data
      return data
    },

    async resolver (id, { estado, observaciones }) {
      const { data } = await api.put(`/verificador/solicitudes/${id}`, { estado, observaciones })
      const idx = this.solicitudes.findIndex(s => s.id === id)
      if (idx !== -1) this.solicitudes[idx] = data.verificacion
      this.cargarContadorPendientes()
      return data
    }
  }
})
