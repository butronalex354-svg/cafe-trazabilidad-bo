import { defineStore } from 'pinia'
import { api } from '@/boot/axios'

// Panel del Administrador: aprobacion de cuentas (RF25) y reportes
// generales de supervision del sistema.
export const useAdministradorStore = defineStore('administrador', {
  state: () => ({
    usuarios: [],
    reportes: null,
    auditoria: [],
    lotesParaExportar: [],
    lotesExportados: [],
    reclamos: [],
    resultadosBusqueda: [],
    cargando: false
  }),

  getters: {
    cuentasPendientes: (state) => state.usuarios.filter(u => u.estado === 'pendiente'),
    reclamosPendientes: (state) => state.reclamos.filter(r => r.estado === 'pendiente').length
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
    },

    async cargarAuditoria () {
      const { data } = await api.get('/administrador/auditoria')
      this.auditoria = data
    },

    async cargarLotesParaExportar () {
      this.cargando = true
      try {
        const { data } = await api.get('/administrador/lotes-para-exportar')
        this.lotesParaExportar = data
      } finally {
        this.cargando = false
      }
    },

    async autorizarExportacion (cosechaId) {
      const { data } = await api.put(`/administrador/lotes/${cosechaId}/autorizar-exportacion`)
      this.lotesParaExportar = this.lotesParaExportar.filter(c => c.id !== cosechaId)
      return data
    },

    async cargarLotesExportados () {
      this.cargando = true
      try {
        const { data } = await api.get('/administrador/lotes-exportados')
        this.lotesExportados = data
      } finally {
        this.cargando = false
      }
    },

    async agregarEstadoEnvio (cosechaId, payload) {
      const { data } = await api.post(`/administrador/lotes/${cosechaId}/envio`, payload)
      const lote = this.lotesExportados.find(c => c.id === cosechaId)
      if (lote) lote.envios.push(data.envio)
      return data
    },

    async cargarReclamos (estado = null) {
      this.cargando = true
      try {
        const { data } = await api.get('/administrador/reclamos', { params: estado ? { estado } : {} })
        this.reclamos = data
      } finally {
        this.cargando = false
      }
    },

    async atenderReclamo (id) {
      const { data } = await api.put(`/administrador/reclamos/${id}/atender`)
      const indice = this.reclamos.findIndex(r => r.id === id)
      if (indice !== -1) this.reclamos[indice] = data.reclamo
    },

    async buscarLotes (filtros = {}) {
      this.cargando = true
      try {
        const { data } = await api.get('/administrador/lotes/buscar', { params: filtros })
        this.resultadosBusqueda = data
      } finally {
        this.cargando = false
      }
    }
  }
})
