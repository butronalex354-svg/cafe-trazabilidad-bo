import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Módulo 1 - Producción (RF01-03): perfil del productor, parcelas y cosechas.
export const useProduccionStore = defineStore('produccion', {
  state: () => ({
    perfil: null,
    parcelas: [],
    cosechas: [],
    cargando: false
  }),

  actions: {
    async cargarPerfil () {
      const { data } = await api.get('/productor/perfil')
      this.perfil = data
      return data
    },

    async actualizarPerfil (datos) {
      const { data } = await api.put('/productor/perfil', datos)
      this.perfil = data
      return data
    },

    async cargarParcelas () {
      this.cargando = true
      try {
        const { data } = await api.get('/parcelas')
        this.parcelas = data
      } finally {
        this.cargando = false
      }
    },

    async crearParcela (datos) {
      const { data } = await api.post('/parcelas', datos)
      this.parcelas.unshift({ ...data, cosechas_count: 0 })
      return data
    },

    async editarParcela (id, datos) {
      const { data } = await api.put(`/parcelas/${id}`, datos)
      const idx = this.parcelas.findIndex(p => p.id === id)
      if (idx !== -1) this.parcelas[idx] = { ...this.parcelas[idx], ...data }
      return data
    },

    async eliminarParcela (id) {
      await api.delete(`/parcelas/${id}`)
      this.parcelas = this.parcelas.filter(p => p.id !== id)
    },

    async cargarCosechas (parcelaId = null) {
      this.cargando = true
      try {
        const { data } = await api.get('/cosechas', { params: parcelaId ? { parcela_id: parcelaId } : {} })
        if (parcelaId) {
          // Reemplaza solo las cosechas de ESTA parcela, conservando las que ya
          // se habían cargado de otras (si no, cada parcela que se abre borra
          // los datos de la anterior, aunque su badge de "N cosecha(s)" siga bien).
          this.cosechas = [...this.cosechas.filter(c => c.parcela_id !== parcelaId), ...data]
        } else {
          this.cosechas = data
        }
      } finally {
        this.cargando = false
      }
    },

    async crearCosecha (datos) {
      const { data } = await api.post('/cosechas', datos)
      this.cosechas.unshift(data)
      const parcela = this.parcelas.find(p => p.id === datos.parcela_id)
      if (parcela) parcela.cosechas_count = (parcela.cosechas_count || 0) + 1
      return data
    },

    async editarCosecha (id, datos) {
      const { data } = await api.put(`/cosechas/${id}`, datos)
      const idx = this.cosechas.findIndex(c => c.id === id)
      if (idx !== -1) this.cosechas[idx] = { ...this.cosechas[idx], ...data }
      return data
    },

    async eliminarCosecha (id) {
      const cosecha = this.cosechas.find(c => c.id === id)
      await api.delete(`/cosechas/${id}`)
      this.cosechas = this.cosechas.filter(c => c.id !== id)
      if (cosecha) {
        const parcela = this.parcelas.find(p => p.id === cosecha.parcela_id)
        if (parcela && parcela.cosechas_count > 0) parcela.cosechas_count -= 1
      }
    }
  }
})
