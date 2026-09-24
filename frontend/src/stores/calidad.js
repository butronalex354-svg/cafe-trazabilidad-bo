import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 4 - Calidad (RF06-10): diagnóstico de IA (subida de foto + resultado),
// alertas automáticas, y control de calidad manual por lote.
export const useCalidadStore = defineStore('calidad', {
  state: () => ({
    diagnosticos: [],
    cargandoDiagnosticos: false,
    subiendoDiagnostico: false,

    alertas: [],
    cargandoAlertas: false,

    controlesCalidad: [],
    cargandoControlesCalidad: false
  }),

  getters: {
    // El globito de alertas cuenta las que siguen SIN RESOLVER (no las no leidas):
    // "leida" solo significa que el productor la vio, pero si la planta sigue
    // enferma, la alerta debe seguir destacada hasta que se marque resuelta.
    alertasNoLeidas: (state) => state.alertas.filter(a => !a.resuelta).length
  },

  actions: {
    async cargarDiagnosticos (parcelaId = null) {
      this.cargandoDiagnosticos = true
      try {
        const { data } = await api.get('/diagnosticos', { params: parcelaId ? { parcela_id: parcelaId } : {} })
        this.diagnosticos = data
      } finally {
        this.cargandoDiagnosticos = false
      }
    },

    async subirDiagnostico ({ parcela_id, foto }) {
      this.subiendoDiagnostico = true
      try {
        const formData = new FormData()
        formData.append('parcela_id', parcela_id)
        formData.append('foto', foto)

        const { data } = await api.post('/diagnosticos', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.diagnosticos.unshift(data)
        return data
      } finally {
        this.subiendoDiagnostico = false
      }
    },

    async eliminarDiagnostico (id) {
      await api.delete(`/diagnosticos/${id}`)
      this.diagnosticos = this.diagnosticos.filter(d => d.id !== id)
    },

    async cargarAlertas () {
      this.cargandoAlertas = true
      try {
        const { data } = await api.get('/alertas')
        this.alertas = data
      } finally {
        this.cargandoAlertas = false
      }
    },

    async marcarAlertaLeida (id) {
      const { data } = await api.put(`/alertas/${id}/leida`)
      const indice = this.alertas.findIndex(a => a.id === id)
      if (indice !== -1) this.alertas[indice] = data
      return data
    },

    async marcarAlertaResuelta (id) {
      const { data } = await api.put(`/alertas/${id}/resuelta`)
      const indice = this.alertas.findIndex(a => a.id === id)
      if (indice !== -1) this.alertas[indice] = data
      return data
    },

    async cargarControlesCalidad (cosechaId = null) {
      this.cargandoControlesCalidad = true
      try {
        const { data } = await api.get('/control-calidad', { params: cosechaId ? { cosecha_id: cosechaId } : {} })
        this.controlesCalidad = data
      } finally {
        this.cargandoControlesCalidad = false
      }
    },

    async crearControlCalidad (payload) {
      const { data } = await api.post('/control-calidad', payload)
      this.controlesCalidad.unshift(data)
      return data
    },

    async actualizarControlCalidad (id, payload) {
      const { data } = await api.put(`/control-calidad/${id}`, payload)
      const indice = this.controlesCalidad.findIndex(c => c.id === id)
      if (indice !== -1) this.controlesCalidad[indice] = data
      return data
    },

    async eliminarControlCalidad (id) {
      await api.delete(`/control-calidad/${id}`)
      this.controlesCalidad = this.controlesCalidad.filter(c => c.id !== id)
    }
  }
})
