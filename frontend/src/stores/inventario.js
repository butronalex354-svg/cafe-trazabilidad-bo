import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 6 - Inventario (RF13): stock disponible por lote y su estado
// (en proceso -> terminado -> exportado).
export const useInventarioStore = defineStore('inventario', {
  state: () => ({
    inventario: [],
    cargando: false
  }),

  actions: {
    async cargarInventario ({ parcelaId = null, estado = null } = {}) {
      this.cargando = true
      try {
        const params = {}
        if (parcelaId) params.parcela_id = parcelaId
        if (estado) params.estado = estado
        const { data } = await api.get('/inventario', { params })
        this.inventario = data
      } finally {
        this.cargando = false
      }
    },

    async cambiarEstado (id, estado) {
      const { data } = await api.put(`/inventario/${id}`, { estado })
      const indice = this.inventario.findIndex(i => i.id === id)
      if (indice !== -1) this.inventario[indice] = data
      return data
    }
  }
})
