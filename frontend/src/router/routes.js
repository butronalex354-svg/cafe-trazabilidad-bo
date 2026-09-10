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
    path: '/productor',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'productor' },
    children: [
      { path: '', component: () => import('@/pages/DashboardPage.vue') }
    ]
  },
  {
    path: '/verificador',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'verificador' },
    children: [
      { path: '', component: () => import('@/pages/DashboardPage.vue') }
    ]
  },
  {
    path: '/administrador',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true, rol: 'administrador' },
    children: [
      { path: '', component: () => import('@/pages/DashboardPage.vue') }
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
