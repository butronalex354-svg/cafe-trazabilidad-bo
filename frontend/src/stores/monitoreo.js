import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 2 - Monitoreo (RF04-05): clima por parcela y observaciones del cultivo.
export const useMonitoreoStore = defineStore('monitoreo', {
  state: () => ({
    clima: null,
    cargandoClima: false,
    observaciones: [],
    cargandoObservaciones: false
  }),

  actions: {
    async cargarClima (parcelaId) {
      this.cargandoClima = true
      this.clima = null
      try {
        const { data } = await api.get(`/parcelas/${parcelaId}/clima`)
        this.clima = data
        return data
      } finally {
        this.cargandoClima = false
      }
    },

    async cargarObservaciones (parcelaId = null) {
      this.cargandoObservaciones = true
      try {
        const { data } = await api.get('/observaciones', { params: parcelaId ? { parcela_id: parcelaId } : {} })
        this.observaciones = data
      } finally {
        this.cargandoObservaciones = false
      }
    },

    async crearObservacion ({ parcela_id, fecha, nota, foto }) {
      const formData = new FormData()
      formData.append('parcela_id', parcela_id)
      formData.append('fecha', fecha)
      formData.append('nota', nota)
      if (foto) formData.append('foto', foto)

      const { data } = await api.post('/observaciones', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      this.observaciones.unshift(data)
      return data
    },

    async actualizarObservacion (id, { fecha, nota, foto }) {
      const formData = new FormData()
      formData.append('_method', 'PUT') // PHP no lee archivos en peticiones PUT: se "disfraza" de POST
      formData.append('fecha', fecha)
      formData.append('nota', nota)
      if (foto) formData.append('foto', foto)

      const { data } = await api.post(`/observaciones/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      const indice = this.observaciones.findIndex(o => o.id === id)
      if (indice !== -1) this.observaciones[indice] = data
      return data
    },

    async eliminarObservacion (id) {
      await api.delete(`/observaciones/${id}`)
      this.observaciones = this.observaciones.filter(o => o.id !== id)
    }
  }
})
