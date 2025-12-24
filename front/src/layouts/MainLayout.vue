<template>
  <q-layout view="lHh Lpr lFf">
    <!-- HEADER -->
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

    <!-- DRAWER -->
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
          Módulos del Sistema
        </q-item-label>

        <!-- Dashboard -->
        <q-item dense to="/" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="dashboard" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Dashboard</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Recintos -->
        <q-item dense to="/recintos" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="location_on" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Recintos</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Usuarios -->
<!--        v-if="canPermission('Usuarios')"-->
        <q-item
          dense
          to="/usuarios"
          exact
          clickable
          class="menu-item"
          active-class="menu-active"
          v-close-popup

        >
          <q-item-section avatar>
            <q-icon name="people" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Usuarios</q-item-label>
          </q-item-section>
        </q-item>
<!--        partidos-->
        <q-item dense to="/partidos" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="how_to_reg" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Partidos</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Resultados -->
        <q-item dense to="/resultados" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="how_to_vote" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Resultados</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Reportes -->
        <q-item dense to="/reportes" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="summarize" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Reportes</q-item-label>
          </q-item-section>
        </q-item>

        <!-- Mesas faltantes -->
        <q-item dense to="/mesas-faltantes" exact clickable class="menu-item" active-class="menu-active" v-close-popup>
          <q-item-section avatar>
            <q-icon name="warning_amber" class="text-white"/>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-white">Mesas faltantes</q-item-label>
          </q-item-section>
        </q-item>

        <div class="q-pa-md">
          <div class="text-white-7 text-caption">
            Resultados v{{ $version }}
          </div>
          <div class="text-white-7 text-caption">
            © {{ new Date().getFullYear() }} Resultados · Electorales
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

    <!-- PAGE -->
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

const leftDrawerOpen = ref(false)

function toggleLeftDrawer () {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function canPermission (permission) {
  return (proxy.$store.permissions || []).includes(permission)
}

function logout () {
  proxy.$alert.dialog('¿Desea salir del sistema?')
    .onOk(() => {
      proxy.$axios.post('/logout')
        .then(() => {
          proxy.$store.isLogged = false
          proxy.$store.user = {}
          proxy.$store.permissions = []
          localStorage.removeItem('tokenResultados')
          proxy.$router.push('/login')
        })
        .catch(() => proxy.$alert.error('Error al cerrar sesión. Intente nuevamente.'))
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
