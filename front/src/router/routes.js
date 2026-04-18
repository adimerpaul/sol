const routes = [
  {
    path: '/public',
    component: () => import('layouts/PublicLayout.vue'),
    children: [
      { path: 'recintos', component: () => import('pages/public/PublicRecintosPage.vue') }
    ]
  },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', redirect: '/dashboard/ambos' },
      { path: 'dashboard/mapa', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true, dashboardMode: 'mapa' } },
      { path: 'dashboard/mesas', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true, dashboardMode: 'mesas' } },
      { path: 'dashboard/ambos', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true, dashboardMode: 'both' } },
      { path: 'dashboard/tortas', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true, dashboardMode: 'pie' } },
      { path: 'dashboard/histogramas', component: () => import('pages/IndexPage.vue'), meta: { requiresAuth: true, dashboardMode: 'bar' } },
      { path: 'dashboard/avance-mesas', component: () => import('pages/DashboardAvanceMesasPage.vue'), meta: { requiresAuth: true } },
      { path: 'usuarios', component: () => import('pages/usuarios/Usuarios.vue'), meta: { requiresAuth: true } },
      { path: 'recintos', component: () => import('pages/geo/GeoMaster.vue'), meta: { requiresAuth: true } },
      { path: 'partidos', component: () => import('pages/partidos/Partidos.vue'), meta: { requiresAuth: true } },
      { path: 'admin-user-recintos', component: () => import('pages/usuarios/AdminUserRecintos.vue'), meta: { requiresAuth: true } },
      { path: 'resultados-mesa', component: () => import('pages/resultados/ResultadosMesa.vue'), meta: { requiresAuth: true } },
      { path: 'admin-recintos-mapa', component: () => import('pages/geo/AdminRecintosMapa.vue'), meta: { requiresAuth: true } },
      { path: 'admin-jerarquia-usuarios', component: () => import('pages/usuarios/AdminJerarquiaUsuarios.vue'), meta: { requiresAuth: true } },
      { path: 'admin-recinto-jefe-mapa', component: () => import('pages/usuarios/AdminRecintoJefeMapa.vue'), meta: { requiresAuth: true } },
      { path: 'admin-resultados-mesas', component: () => import('pages/resultados/AdminResultadosMesas.vue'), meta: { requiresAuth: true } },
      { path: 'admin-resultados-mesas-segunda-vuelta', component: () => import('pages/resultados/AdminResultadosMesasSegundaVuelta.vue'), meta: { requiresAuth: true } },
      { path: 'control-ia-mesas', component: () => import('pages/resultados/ControlAiMesas.vue'), meta: { requiresAuth: true } },
      { path: 'reportes', component: () => import('pages/usuarios/Reportes.vue'), meta: { requiresAuth: true } },
      { path: 'reportes-por-municipio', component: () => import('pages/reportes/ReportesMunicipio.vue'), meta: { requiresAuth: true } },
      { path: 'mi-perfil', component: () => import('pages/usuarios/MiPerfil.vue'), meta: { requiresAuth: true } },

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
