<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- HEADER -->
      <q-card-section class="row items-center">
        <q-icon name="account_tree" class="text-primary q-mr-sm" />
        <div>
          <div class="text-subtitle1 text-weight-bold">Jerarquía de Usuarios</div>
          <div class="text-caption text-grey-7">
            Supervisor → muchos Jefes de Recinto → muchos Delegados de Mesa
          </div>
        </div>
        <q-space />
        <q-btn color="primary" icon="refresh" label="Actualizar" no-caps :loading="loading" @click="loadAll" />
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <div class="row">
          <div class="col-12 col-md-4">
            <div class="q-pa-sm">
              <div class="row items-center q-gutter-sm q-mb-sm">
                <q-input v-model="supervisorFilter" dense outlined placeholder="Buscar supervisor..." class="col" clearable>
                  <template v-slot:append><q-icon name="search" /></template>
                </q-input>
              </div>

              <q-list bordered separator>
                <q-item
                  v-for="s in filteredSupervisores"
                  :key="s.id"
                  clickable
                  @click="selectSupervisor(s)"
                  :active="selectedSupervisor?.id === s.id"
                  active-class="bg-blue-1"
                >
                  <q-item-section avatar>
                    <q-avatar rounded>
                      <q-img :src="`${$url}/../images/${s.avatar}`" v-if="s.avatar" />
                      <q-icon name="person" v-else />
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ s.name || 'Sin nombre' }}</q-item-label>
                    <q-item-label caption>{{ s.username }} • {{ s.role }}</q-item-label>
                    <div class="row q-gutter-xs q-mt-xs">
                      <q-badge color="primary" outline>
                        Jefes: {{ s.jefes_count ?? 0 }}
                      </q-badge>
                    </div>
                  </q-item-section>

                  <q-item-section side>
                    <q-icon name="chevron_right" color="grey-6" />
                  </q-item-section>
                </q-item>

                <q-item v-if="!loading && filteredSupervisores.length === 0">
                  <q-item-section class="text-grey-7">Sin supervisores</q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <div class="q-pa-sm">
              <div v-if="!selectedSupervisor" class="text-grey-7 q-pa-md">
                Selecciona un supervisor para asignarle jefes.
              </div>

              <div v-else>
                <div class="row items-center q-col-gutter-sm q-mb-sm">
                  <div class="col">
                    <div class="text-subtitle2 text-weight-bold">
                      Supervisor: {{ selectedSupervisor.name || 'Sin nombre' }}
                    </div>
                    <div class="text-caption text-grey-7">
                      Jefes asignados: {{ assignedJefeIds.length }}
                    </div>
                  </div>

                  <div class="col-auto">
                    <q-btn color="positive" icon="save" label="Guardar jefes" no-caps :loading="savingJefes" @click="saveJefes" />
                  </div>
                </div>

                <q-card flat bordered class="q-pa-sm q-mb-sm bg-grey-1">
                  <div class="row q-col-gutter-sm items-center">
                    <div class="col-12 col-md-8">
                      <q-select
                        v-model="jefeToAdd"
                        dense outlined
                        label="Agregar Jefe de Recinto"
                        :options="jefesOptions"
                        option-label="label"
                        option-value="value"
                        emit-value
                        map-options
                        use-input
                        input-debounce="0"
                        @filter="filterJefes"
                        clearable
                      >
                        <template v-slot:prepend><q-icon name="shield" /></template>
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
                        :disable="!jefeToAdd"
                        @click="addJefe"
                      />
                    </div>
                  </div>
                </q-card>

                <q-markup-table dense bordered class="q-ma-none">
                  <thead>
                  <tr>
                    <th class="text-left">Jefe de Recinto</th>
                    <th class="text-left">Usuario</th>
                    <th class="text-right" width="120">Delegados</th>
                    <th class="text-right" width="80">Quitar</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr
                    v-for="j in assignedJefesDetailed"
                    :key="j.id"
                    @click="selectJefe(j)"
                    style="cursor: pointer"
                    :class="selectedJefe?.id === j.id ? 'bg-blue-1' : ''"
                  >
                    <td>{{ j.name || 'Sin nombre' }}</td>
                    <td class="text-grey-8">{{ j.username }}</td>
                    <td class="text-right">
                      <q-badge color="green" outline>
                        {{ j.delegados_count ?? 0 }}
                      </q-badge>
                    </td>
                    <td class="text-right">
                      <q-btn flat round dense icon="delete" color="negative" @click.stop="removeJefe(j.id)" />
                    </td>
                  </tr>

                  <tr v-if="assignedJefesDetailed.length === 0">
                    <td colspan="4" class="text-center text-grey-7 q-pa-md">
                      Sin jefes asignados
                    </td>
                  </tr>
                  </tbody>
                </q-markup-table>
              </div>
            </div>
            <div class="q-pa-sm">
              <div v-if="!selectedSupervisor" class="text-grey-7 q-pa-md">
                Primero selecciona un supervisor.
              </div>

              <div v-else-if="!selectedJefe" class="text-grey-7 q-pa-md">
                Selecciona un jefe (arriba) para asignarle delegados.
              </div>

              <div v-else>
                <div class="row items-center q-col-gutter-sm q-mb-sm">
                  <div class="col">
                    <div class="text-subtitle2 text-weight-bold">
                      Jefe: {{ selectedJefe.name || 'Sin nombre' }}
                    </div>
                    <div class="text-caption text-grey-7">
                      Delegados asignados: {{ assignedDelegadoIds.length }}
                    </div>
                  </div>

                  <div class="col-auto">
                    <q-btn color="positive" icon="save" label="Guardar delegados" no-caps :loading="savingDelegados" @click="saveDelegados" />
                  </div>
                </div>

                <q-card flat bordered class="q-pa-sm q-mb-sm bg-grey-1">
                  <div class="row q-col-gutter-sm items-center">
                    <div class="col-12 col-md-8">
                      <q-select
                        v-model="delegadoToAdd"
                        dense outlined
                        label="Agregar Delegado de Mesa"
                        :options="delegadosOptions"
                        option-label="label"
                        option-value="value"
                        emit-value
                        map-options
                        use-input
                        input-debounce="0"
                        @filter="filterDelegados"
                        clearable
                      >
                        <template v-slot:prepend><q-icon name="how_to_reg" /></template>
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
                        :disable="!delegadoToAdd"
                        @click="addDelegado"
                      />
                    </div>
                  </div>
                </q-card>

                <q-markup-table dense bordered class="q-ma-none">
                  <thead>
                  <tr>
                    <th class="text-left">Delegado</th>
                    <th class="text-left">Usuario</th>
                    <th class="text-right" width="80">Quitar</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="d in assignedDelegadosDetailed" :key="d.id">
                    <td>{{ d.name || 'Sin nombre' }}</td>
                    <td class="text-grey-8">{{ d.username }}</td>
                    <td class="text-right">
                      <q-btn flat round dense icon="delete" color="negative" @click="removeDelegado(d.id)" />
                    </td>
                  </tr>

                  <tr v-if="assignedDelegadosDetailed.length === 0">
                    <td colspan="3" class="text-center text-grey-7 q-pa-md">
                      Sin delegados asignados
                    </td>
                  </tr>
                  </tbody>
                </q-markup-table>
              </div>
            </div>

<!--            <q-splitter v-model="splitB" horizontal style="height: 100%">-->

<!--              &lt;!&ndash; TOP: JEFES &ndash;&gt;-->
<!--              <template v-slot:before>-->

<!--              </template>-->

<!--              &lt;!&ndash; BOTTOM: DELEGADOS &ndash;&gt;-->
<!--              <template v-slot:after>-->
<!--              </template>-->

<!--            </q-splitter>-->
          </div>
        </div>
<!--        <q-splitter v-model="splitA" style="height: calc(100vh - 180px); min-height: 560px">-->

<!--          &lt;!&ndash; LEFT: SUPERVISORES &ndash;&gt;-->
<!--          <template v-slot:before>-->

<!--          </template>-->

<!--          &lt;!&ndash; RIGHT: (split) JEFES + DELEGADOS &ndash;&gt;-->
<!--          <template v-slot:after>-->
<!--          </template>-->

<!--        </q-splitter>-->
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'AdminJerarquiaUsuarios',

  data () {
    return {
      splitA: 32,
      splitB: 55,

      loading: false,
      savingJefes: false,
      savingDelegados: false,

      supervisores: [],
      jefesAll: [],
      delegadosAll: [],

      supervisorFilter: '',
      selectedSupervisor: null,

      // asignaciones
      assignedJefeIds: [],
      jefeToAdd: null,

      selectedJefe: null,
      assignedDelegadoIds: [],
      delegadoToAdd: null,

      // options (filtrables)
      jefesOptionsAll: [],
      jefesOptions: [],
      delegadosOptionsAll: [],
      delegadosOptions: []
    }
  },

  computed: {
    filteredSupervisores () {
      const t = (this.supervisorFilter || '').toLowerCase().trim()
      if (!t) return this.supervisores
      return (this.supervisores || []).filter(s =>
        String(s.name || '').toLowerCase().includes(t) ||
        String(s.username || '').toLowerCase().includes(t)
      )
    },

    assignedJefesDetailed () {
      const map = new Map((this.jefesAll || []).map(x => [x.id, x]))
      return (this.assignedJefeIds || [])
        .map(id => map.get(id))
        .filter(Boolean)
        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')))
    },

    assignedDelegadosDetailed () {
      const map = new Map((this.delegadosAll || []).map(x => [x.id, x]))
      return (this.assignedDelegadoIds || [])
        .map(id => map.get(id))
        .filter(Boolean)
        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')))
    }
  },

  mounted () {
    this.loadAll()
  },

  methods: {
    async loadAll () {
      this.loading = true
      try {
        const [sRes, jRes, dRes] = await Promise.all([
          this.$axios.get('admin/jerarquia/supervisores'),
          this.$axios.get('admin/jerarquia/jefes'),
          this.$axios.get('admin/jerarquia/delegados')
        ])

        this.supervisores = Array.isArray(sRes.data) ? sRes.data : []
        this.jefesAll = Array.isArray(jRes.data) ? jRes.data : []
        this.delegadosAll = Array.isArray(dRes.data) ? dRes.data : []

        this.jefesOptionsAll = this.jefesAll.map(j => ({
          value: j.id,
          label: `${j.name || 'Sin nombre'} — ${j.username || ''}`
        }))
        this.jefesOptions = this.jefesOptionsAll

        this.delegadosOptionsAll = this.delegadosAll.map(d => ({
          value: d.id,
          label: `${d.name || 'Sin nombre'} — ${d.username || ''}`
        }))
        this.delegadosOptions = this.delegadosOptionsAll

        if (!this.selectedSupervisor && this.supervisores.length) {
          await this.selectSupervisor(this.supervisores[0])
        } else if (this.selectedSupervisor?.id) {
          const again = this.supervisores.find(x => x.id === this.selectedSupervisor.id)
          if (again) await this.selectSupervisor(again)
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar la jerarquía')
      } finally {
        this.loading = false
      }
    },

    async selectSupervisor (s) {
      this.selectedSupervisor = { ...s }
      this.selectedJefe = null
      this.assignedDelegadoIds = []
      this.delegadoToAdd = null
      this.jefeToAdd = null
      this.loading = true

      try {
        const r = await this.$axios.get(`admin/jerarquia/supervisores/${s.id}/jefes`)
        this.loading = false
        const assigned = Array.isArray(r.data) ? r.data : []
        this.assignedJefeIds = assigned.map(x => x.id)
      } catch (e) {
        this.assignedJefeIds = []
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar jefes del supervisor')
      }
    },

    async selectJefe (j) {
      this.selectedJefe = { ...j }
      this.assignedDelegadoIds = []
      this.delegadoToAdd = null

      await this.loadDelegadosDeJefe(this.selectedJefe.id)
    },

    async loadDelegadosDeJefe (jefeId) {
      try {
        const r = await this.$axios.get(`admin/jerarquia/jefes/${jefeId}/delegados`)
        const assigned = Array.isArray(r.data) ? r.data : []
        this.assignedDelegadoIds = assigned.map(x => x.id)
      } catch (e) {
        this.assignedDelegadoIds = []
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar delegados del jefe')
      }
    },

    filterJefes (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) { this.jefesOptions = this.jefesOptionsAll; return }
        this.jefesOptions = this.jefesOptionsAll.filter(o => String(o.label || '').toLowerCase().includes(needle))
      })
    },

    filterDelegados (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) { this.delegadosOptions = this.delegadosOptionsAll; return }
        this.delegadosOptions = this.delegadosOptionsAll.filter(o => String(o.label || '').toLowerCase().includes(needle))
      })
    },

    addJefe () {
      const id = this.jefeToAdd
      if (!id) return
      if ((this.assignedJefeIds || []).includes(id)) {
        this.$alert?.error('Ese jefe ya está asignado')
        return
      }
      this.assignedJefeIds = [...(this.assignedJefeIds || []), id]
      this.jefeToAdd = null
    },

    removeJefe (id) {
      this.assignedJefeIds = (this.assignedJefeIds || []).filter(x => x !== id)
      if (this.selectedJefe?.id === id) {
        this.selectedJefe = null
        this.assignedDelegadoIds = []
      }
    },

    async saveJefes () {
      if (!this.selectedSupervisor?.id) return
      const supId = this.selectedSupervisor.id

      this.savingJefes = true
      try {
        await this.$axios.put(`admin/jerarquia/supervisores/${supId}/jefes`, { jefes: this.assignedJefeIds })
        this.$alert?.success('Jefes guardados')

        await this.loadAll()

        const againS = (this.supervisores || []).find(x => x.id === supId)
        if (againS) await this.selectSupervisor(againS)
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar jefes')
      } finally {
        this.savingJefes = false
      }
    },

    addDelegado () {
      const id = this.delegadoToAdd
      if (!id) return
      if ((this.assignedDelegadoIds || []).includes(id)) {
        this.$alert?.error('Ese delegado ya está asignado')
        return
      }
      this.assignedDelegadoIds = [...(this.assignedDelegadoIds || []), id]
      this.delegadoToAdd = null
    },

    removeDelegado (id) {
      this.assignedDelegadoIds = (this.assignedDelegadoIds || []).filter(x => x !== id)
    },

    async saveDelegados () {
      if (!this.selectedJefe?.id) return

      const jefeId = this.selectedJefe.id  // ✅ guarda id antes de recargar
      this.savingDelegados = true

      try {
        await this.$axios.put(`admin/jerarquia/jefes/${jefeId}/delegados`, {
          delegados: this.assignedDelegadoIds
        })

        this.$alert?.success('Delegados guardados')

        // ✅ recargar listas (conteos de delegados_count)
        await this.loadAll()

        // ✅ rehidratar el jefe seleccionado con el array nuevo
        const againJ = (this.jefesAll || []).find(x => x.id === jefeId)
        if (againJ) {
          await this.selectJefe(againJ) // ✅ ahora sí espera cargar delegados
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar delegados')
      } finally {
        this.savingDelegados = false
      }
    }
  }
}
</script>
