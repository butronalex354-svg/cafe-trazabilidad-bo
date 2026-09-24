import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 5 - Procesamiento (RF11-12): etapas de post-cosecha de un lote
// (despulpado, fermentación, lavado, secado, tostado, envasado, embalaje)
// y su línea de tiempo en orden cronológico del proceso.
export const useProcesamientoStore = defineStore('procesamiento', {
  state: () => ({
    etapas: [],
    cargandoEtapas: false
  }),

  actions: {
    async cargarEtapas (cosechaId) {
      this.cargandoEtapas = true
      try {
        const { data } = await api.get('/etapas-procesamiento', { params: { cosecha_id: cosechaId } })
        this.etapas = data
      } finally {
        this.cargandoEtapas = false
      }
    },

    async crearEtapa (payload) {
      const { data } = await api.post('/etapas-procesamiento', payload)
      this.etapas.push(data)
      return data
    },

    async eliminarEtapa (id) {
      await api.delete(`/etapas-procesamiento/${id}`)
      this.etapas = this.etapas.filter(e => e.id !== id)
    }
  }
})
