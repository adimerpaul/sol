const routes = [
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true } },
      { path: 'usuarios', component: () => import('pages/usuarios/Usuarios.vue'), meta: { requiresAuth: true } },
      { path: 'recintos', component: () => import('pages/geo/GeoMaster.vue'), meta: { requiresAuth: true } },
      { path: 'partidos', component: () => import('pages/partidos/Partidos.vue'), meta: { requiresAuth: true } },
      { path: 'admin-user-recintos', component: () => import('pages/usuarios/AdminUserRecintos.vue'), meta: { requiresAuth: true } },
      { path: 'resultados-mesa', component: () => import('pages/resultados/ResultadosMesa.vue'), meta: { requiresAuth: true } },

    ]
  },
  {
    path: '/login',
    component: () => import('layouts/Login.vue')
  },
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue')
  }
]

export default routes
