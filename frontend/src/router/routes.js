const routes = [
  {
    path: '/login',
    component: () => import('@/pages/LoginPage.vue')
  },
  {
    path: '/registro',
    component: () => import('@/pages/RegisterPage.vue')
  },
  {
    // RF19: pagina publica (sin login) a la que apunta el QR de cada lote.
    path: '/trazabilidad/:codigo',
    component: () => import('@/pages/TrazabilidadPublicaPage.vue')
  },
  {
    path: '/productor',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'productor' },
    children: [
      { path: '', component: () => import('@/pages/DashboardPage.vue') },
      { path: 'parcelas', component: () => import('@/pages/ParcelasPage.vue') },
      { path: 'produccion', component: () => import('@/pages/ProduccionPage.vue') },
      { path: 'monitoreo', component: () => import('@/pages/MonitoreoPage.vue') },
      { path: 'procesamiento', component: () => import('@/pages/ProcesamientoPage.vue') },
      { path: 'calidad', component: () => import('@/pages/CalidadPage.vue') },
      { path: 'inventario', component: () => import('@/pages/InventarioPage.vue') },
      { path: 'historial', component: () => import('@/pages/HistorialProductorPage.vue') }
    ]
  },
  {
    path: '/verificador',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'verificador' },
    children: [
      { path: '', component: () => import('@/pages/VerificadorPage.vue'), meta: { estadoFiltro: null } },
      { path: 'pendientes', component: () => import('@/pages/VerificadorPage.vue'), meta: { estadoFiltro: 'pendiente' } },
      { path: 'observados', component: () => import('@/pages/VerificadorPage.vue'), meta: { estadoFiltro: 'observado' } },
      { path: 'verificados', component: () => import('@/pages/VerificadorPage.vue'), meta: { estadoFiltro: 'verificado' } },
      { path: 'rechazados', component: () => import('@/pages/VerificadorPage.vue'), meta: { estadoFiltro: 'rechazado' } },
      { path: 'historial', component: () => import('@/pages/HistorialVerificadorPage.vue') }
    ]
  },
  {
    path: '/administrador',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'administrador' },
    children: [
      { path: '', component: () => import('@/pages/AdministradorPage.vue') },
      { path: 'auditoria', component: () => import('@/pages/AuditoriaPage.vue') },
      { path: 'exportacion', component: () => import('@/pages/ExportacionPage.vue') },
      { path: 'reclamos', component: () => import('@/pages/ReclamosPage.vue') },
      { path: 'buscar', component: () => import('@/pages/BuscarLotesPage.vue') },
      { path: 'catalogos', component: () => import('@/pages/CatalogosPage.vue') }
    ]
  },
  {
    path: '/',
    redirect: '/login'
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  }
]

export default routes
