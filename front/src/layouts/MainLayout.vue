<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-white text-black" bordered>
      <q-toolbar>
        <q-btn
          flat
          color="primary"
          :icon="leftDrawerOpen ? 'keyboard_double_arrow_left' : 'keyboard_double_arrow_right'"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          unelevated
          dense
        />

        <div class="row items-center q-gutter-sm">
          <div class="text-subtitle1 text-weight-medium" style="line-height: 0.9">
            Resultados Electorales
          </div>
        </div>

        <q-space />

        <div class="row items-center q-gutter-sm">
          <q-btn-dropdown flat unelevated no-caps dropdown-icon="expand_more">
            <template v-slot:label>
              <div class="row items-center no-wrap q-gutter-sm">
                <q-avatar rounded>
                  <q-img :src="`${$url}/../images/${$store.user.avatar}`" width="40px" height="40px" v-if="$store.user.avatar"/>
                  <q-icon name="person" v-else />
                </q-avatar>
                <div class="text-left" style="line-height: 1">
                  <div class="ellipsis" style="max-width: 130px;">
                    {{ $store.user.username }}
                  </div>
                </div>
              </div>
            </template>

            <q-item clickable v-close-popup>
              <q-item-section>
                <q-item-label class="text-grey-7">
                  Permisos asignados
                </q-item-label>
                <q-item-label caption class="q-mt-xs">
                  <div class="row q-col-gutter-xs" style="min-width: 150px; max-width: 150px;">
                    <q-chip
                      v-for="(p, i) in $store.permissions"
                      :key="i"
                      dense
                      color="grey-3"
                      text-color="black"
                      size="12px"
                      class="q-mr-xs q-mb-xs"
                    >
                      {{ p }}
                    </q-chip>
                    <q-badge v-if="!$store.permissions?.length" color="grey-5" outline>Sin permisos</q-badge>
                  </div>
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-separator />

            <q-item clickable v-ripple @click="logout" v-close-popup>
              <q-item-section avatar>
                <q-icon name="logout" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Salir</q-item-label>
              </q-item-section>
            </q-item>
          </q-btn-dropdown>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      bordered
      show-if-above
      :width="220"
      :breakpoint="500"
      class="bg-primary text-white"
    >
      <q-list class="q-pb-none">
        <q-item-label header class="text-center q-pa-none q-pt-md">
          <q-avatar size="64px" class="q-mb-sm bg-white" rounded>
            <q-img src="/logo.png" width="90px" />
          </q-avatar>
          <div class="text-weight-bold text-white">Resultados</div>
          <div class="text-caption text-white">Electorales</div>
        </q-item-label>

        <q-item-label header class="q-px-md text-grey-3 q-mt-sm">
          Modulos del Sistema
        </q-item-label>

        <q-item
          dense
          to="/dashboard/ambos"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.DASHBOARD)"
        >
          <q-item-section avatar>
            <q-icon name="dashboard" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Dashboard</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/recintos"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.RECINTOS)"
        >
          <q-item-section avatar>
            <q-icon name="location_on" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Recintos</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/admin-recintos-mapa"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.RECINTOS_MAPA)"
        >
          <q-item-section avatar>
            <q-icon name="map" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Recintos Mapa</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/usuarios"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.DELEGADOS_MESA)"
        >
          <q-item-section avatar>
            <q-icon name="people" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Delegados de Mesa</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/partidos"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.PARTIDOS)"
        >
          <q-item-section avatar>
            <q-icon name="how_to_reg" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Partidos</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/admin-user-recintos"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.ASIGNAR_RECINTOS)"
        >
          <q-item-section avatar>
            <q-icon name="admin_panel_settings" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Asignar Recintos</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/admin-jerarquia-usuarios"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.JERARQUIA_USUARIOS)"
        >
          <q-item-section avatar>
            <q-icon name="account_tree" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Jerarquia Usuarios</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/admin-recinto-jefe-mapa"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.MAPA_ASIGNAR_JEFES)"
        >
          <q-item-section avatar>
            <q-icon name="pin_drop" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Mapa Asignar Jefes</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/admin-resultados-mesas"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.SUPERADMIN_MESAS)"
        >
          <q-item-section avatar>
            <q-icon name="table_view" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">SuperAdmin Mesas</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/control-ia-mesas"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canPermission(PERM.CONTROL_IA_MESAS) || canProfileRole()"
        >
          <q-item-section avatar>
            <q-icon name="auto_awesome" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Control IA Mesas</q-item-label>
          </q-item-section>
        </q-item>

        <template v-if="isAdministrator() && canPermission(PERM.DASHBOARD)">
          <q-item-label header class="q-px-md text-grey-3 q-mt-sm">
            Dashboard Admin
          </q-item-label>

          <q-item dense to="/dashboard/mapa" clickable class="menu-item" active-class="menu-active" v-close-popup>
            <q-item-section avatar><q-icon name="map" class="text-white"/></q-item-section>
            <q-item-section><q-item-label class="text-white">PerMapa</q-item-label></q-item-section>
          </q-item>

          <q-item dense to="/dashboard/mesas" clickable class="menu-item" active-class="menu-active" v-close-popup>
            <q-item-section avatar><q-icon name="grid_view" class="text-white"/></q-item-section>
            <q-item-section><q-item-label class="text-white">PerMesas</q-item-label></q-item-section>
          </q-item>

          <q-item dense to="/dashboard/ambos" clickable class="menu-item" active-class="menu-active" v-close-popup>
            <q-item-section avatar><q-icon name="dashboard_customize" class="text-white"/></q-item-section>
            <q-item-section><q-item-label class="text-white">Ambos</q-item-label></q-item-section>
          </q-item>

          <q-item dense to="/dashboard/tortas" clickable class="menu-item" active-class="menu-active" v-close-popup>
            <q-item-section avatar><q-icon name="pie_chart" class="text-white"/></q-item-section>
            <q-item-section><q-item-label class="text-white">Tortas</q-item-label></q-item-section>
          </q-item>

          <q-item dense to="/dashboard/histogramas" clickable class="menu-item" active-class="menu-active" v-close-popup>
            <q-item-section avatar><q-icon name="bar_chart" class="text-white"/></q-item-section>
            <q-item-section><q-item-label class="text-white">Histogramas</q-item-label></q-item-section>
          </q-item>
        </template>

        <q-item
          dense
          to="/reportes"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canProfileRole()"
        >
          <q-item-section avatar>
            <q-icon name="assessment" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Reportes</q-item-label>
          </q-item-section>
        </q-item>

        <q-item
          dense
          to="/mi-perfil"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup
          v-if="canProfileRole()"
        >
          <q-item-section avatar>
            <q-icon name="manage_accounts" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Mi perfil</q-item-label>
          </q-item-section>
        </q-item>

        <div class="q-pa-md">
          <div class="text-white-7 text-caption">
            Resultados v{{ $version }}
          </div>
          <div class="text-white-7 text-caption">
            @ {{ new Date().getFullYear() }} Resultados  Electorales
          </div>
        </div>

        <q-item clickable class="text-white" @click="logout" v-close-popup>
          <q-item-section avatar>
            <q-icon name="logout" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Salir</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container class="bg-grey-2">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { getCurrentInstance, ref } from 'vue'
import { useCounterStore } from 'stores/example-store'

const { proxy } = getCurrentInstance()
useCounterStore()

const PERM = {
  DASHBOARD: 'Dashboard',
  RECINTOS: 'Recintos',
  RECINTOS_MAPA: 'Recintos Mapa',
  DELEGADOS_MESA: 'Delegados de Mesa',
  PARTIDOS: 'Partidos',
  ASIGNAR_RECINTOS: 'Asignar Recintos',
  JERARQUIA_USUARIOS: 'Jerarquia Usuarios',
  MAPA_ASIGNAR_JEFES: 'Mapa Asignar Jefes',
  SUPERADMIN_MESAS: 'SuperAdmin Mesas',
  CONTROL_IA_MESAS: 'Control IA Mesas'
}

const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function canPermission (permission) {
  return (proxy.$store.permissions || []).includes(permission)
}

function canProfileRole () {
  const role = proxy.$store?.user?.role || ''
  return role === 'Administrador' || role === 'Supervisor'
}

function isAdministrator () {
  return (proxy.$store?.user?.role || '') === 'Administrador'
}

function logout () {
  proxy.$alert.dialog('Desea salir del sistema?')
    .onOk(() => {
      proxy.$axios.post('/logout')
        .then(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          proxy.$store.permissions = []
          localStorage.removeItem('tokenResultados')
          proxy.$router.push('/login')
        })
        .catch(() => proxy.$alert.error('Error al cerrar sesion. Intente nuevamente.'))
    })
}
</script>

<style scoped>
.menu-item {
  border-radius: 10px;
  margin: 4px 8px;
  padding: 4px 6px;
}

.menu-active {
  background: rgba(255, 255, 255, 0.15);
  color: #fff !important;
  border-radius: 10px;
}
</style>
