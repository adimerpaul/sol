<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- HEADER -->
      <q-card-section class="row items-center">
        <q-icon name="admin_panel_settings" class="text-primary q-mr-sm" />
        <div>
          <div class="text-subtitle1 text-weight-bold">Asignación de Recintos a Usuarios</div>
          <div class="text-caption text-grey-7">
            Solo ORURO • Un recinto puede estar asignado a muchos usuarios • Mesas se calculan por recinto
          </div>
        </div>

        <q-space />

        <q-btn
          outline
          color="warning"
          icon="info"
          label="Recintos sin asignar"
          no-caps
          class="q-mr-sm"
          :loading="loadingNoAsignados"
          @click="openNoAsignados"
        />

        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar"
          no-caps
          :loading="loading"
          @click="loadAll"
        />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <q-splitter v-model="split" style="height: calc(100vh - 180px); min-height: 520px">

          <!-- LEFT: USERS -->
          <template v-slot:before>
            <div class="q-pa-sm">

              <div class="row items-center q-gutter-sm q-mb-sm">
                <q-input v-model="userFilter" dense outlined placeholder="Buscar usuario..." class="col" clearable>
                  <template v-slot:append><q-icon name="search" /></template>
                </q-input>
              </div>

              <q-list bordered separator>
                <q-item
                  v-for="u in filteredUsers"
                  :key="u.id"
                  clickable
                  @click="selectUser(u)"
                  :active="selectedUser?.id === u.id"
                  active-class="bg-blue-1"
                >
                  <q-item-section avatar>
                    <q-avatar rounded>
                      <q-img :src="`${$url}/../images/${u.avatar}`" v-if="u.avatar" />
                      <q-icon name="person" v-else />
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ u.name || 'Sin nombre' }}</q-item-label>
                    <q-item-label caption>{{ u.username }} • {{ u.role }}</q-item-label>

                    <div class="row q-gutter-xs q-mt-xs">
                      <q-badge color="primary" outline>
                        Recintos: {{ u.recintos_count ?? (u.recintos || []).length }}
                      </q-badge>
                      <q-badge color="green" outline>
                        Mesas: {{ u.mesas_count ?? 0 }}
                      </q-badge>
                    </div>
                  </q-item-section>

                  <q-item-section side>
                    <q-icon name="chevron_right" color="grey-6" />
                  </q-item-section>
                </q-item>

                <q-item v-if="!loading && filteredUsers.length === 0">
                  <q-item-section class="text-grey-7">Sin usuarios</q-item-section>
                </q-item>
              </q-list>
            </div>
          </template>

          <!-- RIGHT: RECINTOS -->
          <template v-slot:after>
            <div class="q-pa-sm">

              <div v-if="!selectedUser" class="text-grey-7 q-pa-md">
                Selecciona un usuario para asignarle recintos.
              </div>

              <div v-else>
                <div class="row items-center q-col-gutter-sm q-mb-sm">
                  <div class="col">
                    <div class="text-subtitle2 text-weight-bold">
                      {{ selectedUser.name || 'Sin nombre' }} ({{ selectedUser.username }})
                    </div>
                    <div class="text-caption text-grey-7">
                      Recintos asignados: {{ assignedIds.length }} • Mesas asignadas: {{ assignedMesasCount }}
                    </div>
                  </div>

                  <div class="col-auto">
                    <q-btn
                      color="positive"
                      icon="save"
                      label="Guardar"
                      no-caps
                      :loading="saving"
                      @click="saveAssignments"
                    />
                  </div>
                </div>

                <q-card flat bordered class="q-pa-sm q-mb-sm bg-grey-1">
                  <div class="row q-col-gutter-sm items-center">
                    <div class="col-12 col-md-8">
                      <q-select
                        v-model="recintoToAdd"
                        dense outlined
                        label="Agregar recinto (ORURO)"
                        :options="recintosOptions"
                        option-label="label"
                        option-value="value"
                        emit-value
                        map-options
                        use-input
                        input-debounce="0"
                        @filter="filterRecintos"
                        clearable
                      >
                        <template v-slot:prepend><q-icon name="place" /></template>
                      </q-select>
                    </div>

                    <div class="col-12 col-md-4">
                      <q-btn
                        outline
                        color="primary"
                        icon="add"
                        label="Agregar"
                        no-caps
                        class="full-width"
                        :disable="!recintoToAdd"
                        @click="addRecinto"
                      />
                    </div>
                  </div>
                </q-card>

                <q-markup-table dense bordered class="q-ma-none">
                  <thead>
                  <tr>
                    <th class="text-left">Recinto</th>
                    <th class="text-left">Ubicación</th>
                    <th class="text-right" width="90">Mesas</th>
                    <th class="text-right" width="80">Quitar</th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr v-for="r in assignedRecintosDetailed" :key="r.id">
                    <td>{{ r.nombre }}</td>
                    <td class="text-grey-8">
                      {{ r.localidad?.nombre }} • {{ r.municipio?.nombre }} • {{ r.provincia?.nombre }}
                    </td>
                    <td class="text-right">
                      <q-badge color="green" outline>
                        {{ r.mesas_count ?? 0 }}
                      </q-badge>
                    </td>
                    <td class="text-right">
                      <q-btn flat round dense icon="delete" color="negative" @click="removeRecinto(r.id)" />
                    </td>
                  </tr>

                  <tr v-if="assignedRecintosDetailed.length === 0">
                    <td colspan="4" class="text-center text-grey-7 q-pa-md">
                      Sin recintos asignados
                    </td>
                  </tr>
                  </tbody>
                </q-markup-table>
              </div>
            </div>
          </template>
        </q-splitter>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>

    <!-- DIALOG: RECINTOS NO ASIGNADOS -->
    <q-dialog v-model="noAsignadosDialog" persistent>
      <q-card style="min-width: 720px; max-width: 92vw">
        <q-card-section class="row items-center">
          <div>
            <div class="text-h6">Recintos sin asignar</div>
            <div class="text-caption text-grey-7">
              Total: {{ noAsignados.length }} (solo ORURO)
            </div>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="noAsigFilter" dense outlined placeholder="Buscar recinto..." clearable class="q-mb-sm">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>

          <q-markup-table dense bordered class="q-ma-none">
            <thead>
            <tr>
              <th>#</th>
              <th class="text-left">Recinto</th>
              <th class="text-left">Ubicación</th>
              <th class="text-right" width="90">Mesas</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(r,i) in filteredNoAsignados" :key="r.id">
              <td>{{ i + 1 }}</td>
              <td>{{ r.nombre }}</td>
              <td class="text-grey-8">
                {{ r.localidad?.nombre }} • {{ r.municipio?.nombre }} • {{ r.provincia?.nombre }}
<!--                <pre>{{r}}</pre>-->
              </td>
              <td class="text-right">
                <q-badge color="orange" outline>
                  {{ r.mesas_count ?? 0 }}
                </q-badge>
              </td>
            </tr>

            <tr v-if="filteredNoAsignados.length === 0">
              <td colspan="3" class="text-center text-grey-7 q-pa-md">
                Sin resultados
              </td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
export default {
  name: 'AdminUserRecintosPage',

  data () {
    return {
      split: 38,
      loading: false,
      saving: false,

      users: [],
      recintos: [],

      userFilter: '',
      selectedUser: null,

      // working set
      assignedIds: [],
      recintoToAdd: null,

      // select filtering
      recintosOptions: [],
      recintosOptionsAll: [],

      // dialog recintos no asignados
      noAsignadosDialog: false,
      loadingNoAsignados: false,
      noAsignados: [],
      noAsigFilter: ''
    }
  },

  computed: {
    filteredUsers () {
      const t = (this.userFilter || '').toLowerCase().trim()
      if (!t) return this.users
      return (this.users || []).filter(u =>
        String(u.name || '').toLowerCase().includes(t) ||
        String(u.username || '').toLowerCase().includes(t) ||
        String(u.role || '').toLowerCase().includes(t)
      )
    },

    assignedRecintosDetailed () {
      const map = new Map((this.recintos || []).map(r => [r.id, r]))
      return (this.assignedIds || [])
        .map(id => map.get(id))
        .filter(Boolean)
        .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre)))
    },

    assignedMesasCount () {
      return (this.assignedRecintosDetailed || []).reduce((acc, r) => acc + Number(r.mesas_count || 0), 0)
    },

    filteredNoAsignados () {
      const t = (this.noAsigFilter || '').toLowerCase().trim()
      if (!t) return this.noAsignados
      return (this.noAsignados || []).filter(r =>
        String(r.nombre || '').toLowerCase().includes(t) ||
        String(r.localidad?.nombre || '').toLowerCase().includes(t) ||
        String(r.municipio?.nombre || '').toLowerCase().includes(t) ||
        String(r.provincia?.nombre || '').toLowerCase().includes(t) ||
        String(
          `${r.localidad?.nombre || ''} • ${r.municipio?.nombre || ''} • ${r.provincia?.nombre || ''}`
        ).toLowerCase().includes(t)
      // Oruro • Oruro • Cercado que tambien filtre asi concatenado
      )
    }
  },

  mounted () {
    this.loadAll()
  },

  methods: {
    async loadAll () {
      this.loading = true
      try {
        const [uRes, rRes] = await Promise.all([
          this.$axios.get('admin/users-recintos'),
          this.$axios.get('admin/recintos-oruro')
        ])

        this.users = Array.isArray(uRes.data) ? uRes.data : []
        this.recintos = Array.isArray(rRes.data) ? rRes.data : []

        this.recintosOptionsAll = this.recintos.map(r => ({
          value: r.id,
          label: `${r.nombre} — ${r.localidad?.nombre || ''} / ${r.municipio?.nombre || ''}`
        }))
        this.recintosOptions = this.recintosOptionsAll

        if (!this.selectedUser && this.users.length) {
          this.selectUser(this.users[0])
        } else if (this.selectedUser?.id) {
          const again = this.users.find(x => x.id === this.selectedUser.id)
          if (again) this.selectUser(again)
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar el panel')
      } finally {
        this.loading = false
      }
    },

    selectUser (u) {
      this.selectedUser = { ...u }
      this.assignedIds = (u.recintos || []).map(r => r.id)
      this.recintoToAdd = null
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.recintosOptions = this.recintosOptionsAll
          return
        }
        this.recintosOptions = this.recintosOptionsAll.filter(o =>
          String(o.label || '').toLowerCase().includes(needle)
        )
      })
    },

    addRecinto () {
      const id = this.recintoToAdd
      if (!id) return
      if ((this.assignedIds || []).includes(id)) {
        this.$alert?.error('Ese recinto ya está asignado')
        return
      }
      this.assignedIds = [...(this.assignedIds || []), id]
      this.recintoToAdd = null
    },

    removeRecinto (id) {
      this.assignedIds = (this.assignedIds || []).filter(x => x !== id)
    },

    async saveAssignments () {
      if (!this.selectedUser?.id) return
      this.saving = true
      try {
        await this.$axios.put(`admin/users/${this.selectedUser.id}/recintos`, {
          recintos: this.assignedIds
        })
        this.$alert?.success('Asignaciones guardadas')
        await this.loadAll()
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
      }
    },

    async openNoAsignados () {
      this.loadingNoAsignados = true
      try {
        const r = await this.$axios.get('admin/recintos-no-asignados')
        this.noAsignados = Array.isArray(r.data) ? r.data : []
        this.noAsignadosDialog = true
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar recintos no asignados')
      } finally {
        this.loadingNoAsignados = false
      }
    }
  }
}
</script>
