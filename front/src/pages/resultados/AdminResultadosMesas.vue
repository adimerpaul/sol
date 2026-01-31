<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- header -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <div class="text-h6 text-weight-bold">Resultados - Mesas (SuperAdmin)</div>
          <div class="text-caption text-grey-7">
            1,057 mesas • filtra por recinto, asignación, estado y resultados.
          </div>
        </div>

        <!-- filtros -->
        <div class="col-12 col-md-8">
          <div class="row q-col-gutter-sm justify-end">

            <div class="col-12 col-sm-4">
              <q-select
                v-model="filters.recinto_id"
                :options="recintosOpt"
                option-label="nombre"
                option-value="id"
                emit-value map-options
                use-input input-debounce="250"
                dense outlined clearable
                label="Recinto"
                @filter="filterRecintos"
              />
            </div>

            <div class="col-6 col-sm-2">
              <q-input v-model="filters.mesa" dense outlined label="Mesa #" type="number" />
            </div>

            <div class="col-6 col-sm-3">
              <q-select
                v-model="filters.asignado"
                dense outlined
                label="Delegado"
                :options="[
                  {label:'Todos', value:'ALL'},
                  {label:'Asignado', value:'YES'},
                  {label:'Sin asignar', value:'NO'},
                ]"
                emit-value map-options
              />
            </div>

            <div class="col-6 col-sm-3">
              <q-select
                v-model="filters.estado"
                dense outlined clearable
                label="Estado"
                :options="estadoOptions"
              />
            </div>

            <div class="col-6 col-sm-3">
              <q-select
                v-model="filters.con_resultado"
                dense outlined
                label="Resultado"
                :options="[
                  {label:'Todos', value:'ALL'},
                  {label:'Con resultado', value:'YES'},
                  {label:'Sin resultado', value:'NO'},
                ]"
                emit-value map-options
              />
            </div>

            <div class="col-12 col-sm-3">
              <q-btn
                color="primary"
                icon="search"
                label="Buscar"
                no-caps
                class="full-width"
                :loading="loading"
                @click="refresh()"
              />
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- tabla -->
      <q-table
        flat bordered
        :rows="rows"
        :columns="columns"
        row-key="id"
        :loading="loading"
        v-model:pagination="pagination"
        @request="onRequest"
        binary-state-sort
        dense
        wrap-cells
      >
        <template v-slot:top-left>
          <div class="row items-center q-col-gutter-sm">
            <q-chip outline color="primary">Total (página): {{ rows.length }}</q-chip>
            <q-chip outline color="positive">Asignadas: {{ countAsignadas }}</q-chip>
            <q-chip outline color="negative">Sin delegado: {{ countSinDelegado }}</q-chip>
          </div>
        </template>

        <!-- recinto -->
        <template v-slot:body-cell-recinto="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.recinto?.nombre }}</div>
            <div class="text-caption text-grey-7">Mesa: {{ props.row.numero_mesa }}</div>
          </q-td>
        </template>

        <!-- delegado -->
        <template v-slot:body-cell-delegado="props">
          <q-td :props="props">
            <q-badge
              outline
              :color="props.row.delegado ? 'positive' : 'negative'"
            >
              {{ props.row.delegado ? (props.row.delegado.name + ' (' + props.row.delegado.username + ')') : 'SIN ASIGNAR' }}
            </q-badge>
          </q-td>
        </template>

        <!-- estado -->
        <template v-slot:body-cell-estado="props">
          <q-td :props="props">
            <q-chip dense text-color="white" :color="colorEstado(props.row.estado)">
              {{ props.row.estado }}
            </q-chip>
          </q-td>
        </template>

        <!-- avisos/etapas -->
        <template v-slot:body-cell-etapas="props">
          <q-td :props="props">
            <div class="row items-center q-col-gutter-xs">
              <q-chip dense size="12px" :color="b(props.row.resultado?.aviso_antes)" text-color="white">Antes</q-chip>
              <q-chip dense size="12px" :color="b(props.row.resultado?.aviso_manana)" text-color="white">Mañana</q-chip>
              <q-chip dense size="12px" :color="b(props.row.resultado?.aviso_mediodia)" text-color="white">Mediodía</q-chip>
              <q-chip dense size="12px" :color="b(props.row.resultado?.aviso_tarde)" text-color="white">Tarde</q-chip>
              <q-chip dense size="12px" :color="b(props.row.resultado?.etapa_1)" text-color="white">Etapa 1</q-chip>
              <q-chip dense size="12px" :color="b(props.row.resultado?.etapa_2)" text-color="white">Etapa 2</q-chip>
            </div>
          </q-td>
        </template>

        <!-- totales -->
        <template v-slot:body-cell-total="props">
          <q-td :props="props">
            <div class="text-weight-bold">
              {{ props.row.resultado?.total_votos ?? 0 }}
            </div>
            <div class="text-caption text-grey-7">
              Válidos: {{ props.row.resultado?.total_validos ?? 0 }} • B: {{ props.row.resultado?.total_blancos ?? 0 }} • N: {{ props.row.resultado?.total_nulos ?? 0 }}
            </div>
          </q-td>
        </template>

        <!-- acciones -->
        <template v-slot:body-cell-actions="props">
          <q-td :props="props" class="text-center">
            <q-btn-dropdown dense size="10px" color="primary" label="Opciones" no-caps>
              <q-list>
                <q-item clickable v-close-popup @click="openAsignar(props.row)">
                  <q-item-section avatar><q-icon name="person_add" /></q-item-section>
                  <q-item-section><q-item-label>Asignar delegado</q-item-label></q-item-section>
                </q-item>

                <q-item clickable v-close-popup @click="openResultado(props.row)">
                  <q-item-section avatar><q-icon name="how_to_vote" /></q-item-section>
                  <q-item-section><q-item-label>Registrar resultado</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- DIALOG: asignar delegado -->
    <q-dialog v-model="dlgAsignar" persistent>
      <q-card style="width: 520px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Asignar delegado</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgAsignar=false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7 q-mb-sm">
            {{ curMesa?.recinto?.nombre }} • Mesa {{ curMesa?.numero_mesa }}
          </div>

          <q-select
            v-model="delegadoPick"
            :options="delegadosOpt"
            option-label="name"
            option-value="id"
            emit-value map-options
            use-input input-debounce="250"
            dense outlined
            label="Delegado de Mesa"
            clearable
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section>
                  <q-item-label>{{ scope.opt.name }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.username }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>

          <q-select
            v-model="estadoPick"
            class="q-mt-sm"
            dense outlined
            label="Estado"
            :options="estadoOptions"
          />

        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="negative" label="Cancelar" no-caps @click="dlgAsignar=false" />
          <q-btn color="primary" label="Asignar" no-caps :disable="!delegadoPick" :loading="saving" @click="saveAsignar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG: resultado -->
    <q-dialog v-model="dlgResultado" persistent>
      <q-card style="width: 900px; max-width: 98vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Resultado de Mesa</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgResultado=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7">
            {{ resMesa?.recinto?.nombre }} • Mesa {{ resMesa?.numero_mesa }} •
            Delegado: <span class="text-weight-medium">{{ resMesa?.delegado?.name || 'SIN ASIGNAR' }}</span>
          </div>

          <q-banner v-if="!resMesa?.delegado_id" dense class="bg-orange-2 text-black q-mt-sm">
            No puedes registrar resultados si la mesa no tiene delegado asignado.
          </q-banner>

          <div class="row q-col-gutter-sm q-mt-sm">
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section class="text-weight-bold">Avisos / Etapas</q-card-section>
                <q-separator />
                <q-card-section class="q-gutter-sm">
                  <q-toggle v-model="resForm.aviso_antes" label="Aviso antes de comenzar" />
                  <q-toggle v-model="resForm.aviso_manana" label="Aviso mañana" />
                  <q-toggle v-model="resForm.aviso_mediodia" label="Aviso mediodía" />
                  <q-toggle v-model="resForm.aviso_tarde" label="Aviso tarde" />
                  <q-separator />
                  <q-toggle v-model="resForm.etapa_1" label="Etapa 1 (reconocimiento)" />
                  <q-toggle v-model="resForm.etapa_2" label="Etapa 2 (final)" />
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="text-weight-bold">Totales</q-card-section>
                <q-separator />
                <q-card-section class="row q-col-gutter-sm">
                  <div class="col-6">
                    <q-input v-model.number="resForm.total_blancos" type="number" dense outlined label="Blancos" />
                  </div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.total_nulos" type="number" dense outlined label="Nulos" />
                  </div>
                  <div class="col-12">
                    <q-input dense outlined label="Total votos (auto)" :model-value="sumVotos" disable />
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-8">
              <q-card flat bordered>
                <q-card-section class="row items-center">
                  <div class="text-weight-bold">Votos por Partido</div>
                  <q-space />
                  <q-chip outline color="primary">Total: {{ sumVotos }}</q-chip>
                </q-card-section>
                <q-separator />
                <q-card-section style="max-height: 55vh; overflow:auto;">
                  <div
                    v-for="p in partidos"
                    :key="p.id"
                    class="row items-center q-col-gutter-sm q-mb-xs"
                  >
                    <div class="col-12 col-md-6">
                      <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                        {{ p.sigla }}
                      </q-badge>
                      <span class="q-ml-sm">{{ p.nombre }}</span>
                    </div>
                    <div class="col-12 col-md-6">
                      <q-input
                        v-model.number="votosMap[p.id]"
                        type="number"
                        dense outlined
                        label="Votos"
                        min="0"
                      />
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section>
                  <q-input v-model="resForm.observacion" type="textarea" outlined label="Observación" />
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="negative" label="Cerrar" no-caps @click="dlgResultado=false" />
          <q-btn
            color="primary"
            label="Guardar resultado"
            no-caps
            :disable="!resMesa?.delegado_id"
            :loading="saving"
            @click="saveResultado"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
export default {
  name: 'AdminResultadosMesas',
  data () {
    return {
      loading: false,
      saving: false,

      rows: [],
      pagination: { page: 1, rowsPerPage: 25, rowsNumber: 0, sortBy: 'numero_mesa', descending: false },

      filters: {
        recinto_id: null,
        mesa: null,
        asignado: 'ALL',
        estado: null,
        con_resultado: 'ALL'
      },

      estadoOptions: ['PENDIENTE','ASIGNADA','EN_PROCESO','FINALIZADA','OBSERVADA'],

      recintosOpt: [],
      recintosBase: [],

      // asignar
      dlgAsignar: false,
      curMesa: null,
      delegadosOpt: [],
      delegadoPick: null,
      estadoPick: 'ASIGNADA',

      // resultado
      dlgResultado: false,
      resMesa: null,
      partidos: [],
      votosMap: {},
      resForm: {
        aviso_antes: false,
        aviso_manana: false,
        aviso_mediodia: false,
        aviso_tarde: false,
        etapa_1: false,
        etapa_2: false,
        total_blancos: 0,
        total_nulos: 0,
        observacion: ''
      }
    }
  },

  computed: {
    countSinDelegado () {
      return (this.rows || []).filter(x => !x.delegado_id).length
    },
    countAsignadas () {
      return (this.rows || []).filter(x => !!x.delegado_id).length
    },
    sumVotos () {
      let s = 0
      for (const k of Object.keys(this.votosMap || {})) {
        const v = Number(this.votosMap[k] || 0)
        if (!Number.isNaN(v)) s += v
      }
      return s
    }
  },

  async mounted () {
    await this.loadOptions()
    this.refresh()
  },

  methods: {
    colorEstado (e) {
      if (e === 'PENDIENTE') return 'grey-7'
      if (e === 'ASIGNADA') return 'primary'
      if (e === 'EN_PROCESO') return 'orange'
      if (e === 'FINALIZADA') return 'positive'
      if (e === 'OBSERVADA') return 'negative'
      return 'grey-7'
    },
    b (val) {
      return val ? 'positive' : 'grey-6'
    },

    async loadOptions () {
      // recintos
      this.recintosBase = await this.$axios.get('admin/mesas/options/recintos').then(r => r.data)
      this.recintosOpt = this.recintosBase

      // delegados
      this.delegadosOpt = await this.$axios.get('admin/mesas/options/delegados').then(r => r.data)
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) { this.recintosOpt = this.recintosBase; return }
        this.recintosOpt = (this.recintosBase || []).filter(r => (r.nombre || '').toLowerCase().includes(needle))
      })
    },

    refresh () {
      this.onRequest({ pagination: this.pagination })
    },

    async onRequest ({ pagination }) {
      this.loading = true
      try {
        const params = {
          page: pagination.page,
          per_page: pagination.rowsPerPage,

          recinto_id: this.filters.recinto_id || undefined,
          mesa: this.filters.mesa || undefined,
          asignado: this.filters.asignado,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado
        }

        const res = await this.$axios.get('admin/mesas', { params }).then(r => r.data)

        this.rows = res.data || []
        this.pagination = {
          ...pagination,
          rowsNumber: res.total || 0
        }
      } finally {
        this.loading = false
      }
    },

    openAsignar (row) {
      this.curMesa = row
      this.delegadoPick = row.delegado_id || null
      this.estadoPick = row.estado || 'ASIGNADA'
      this.dlgAsignar = true
    },

    async saveAsignar () {
      if (!this.curMesa?.id || !this.delegadoPick) return
      this.saving = true
      try {
        await this.$axios.put(`admin/mesas/${this.curMesa.id}/delegado`, {
          delegado_id: this.delegadoPick,
          estado: this.estadoPick
        })
        this.$alert.success('Delegado asignado')
        this.dlgAsignar = false
        this.refresh()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo asignar')
      } finally {
        this.saving = false
      }
    },

    async openResultado (row) {
      this.saving = true
      try {
        const data = await this.$axios.get(`admin/mesas/${row.id}/resultado`).then(r => r.data)

        this.resMesa = data.mesa
        this.partidos = data.partidos || []

        // reset
        this.votosMap = {}
        this.resForm = {
          aviso_antes: false,
          aviso_manana: false,
          aviso_mediodia: false,
          aviso_tarde: false,
          etapa_1: false,
          etapa_2: false,
          total_blancos: 0,
          total_nulos: 0,
          observacion: ''
        }

        // si ya existe resultado -> cargar
        if (data.resultado) {
          const r = data.resultado

          this.resForm.aviso_antes = !!r.aviso_antes
          this.resForm.aviso_manana = !!r.aviso_manana
          this.resForm.aviso_mediodia = !!r.aviso_mediodia
          this.resForm.aviso_tarde = !!r.aviso_tarde
          this.resForm.etapa_1 = !!r.etapa_1
          this.resForm.etapa_2 = !!r.etapa_2
          this.resForm.total_blancos = Number(r.total_blancos || 0)
          this.resForm.total_nulos = Number(r.total_nulos || 0)
          this.resForm.observacion = r.observacion || ''

          // detalles
          const det = r.detalles || []
          det.forEach(d => {
            this.votosMap[d.partido_id] = Number(d.votos || 0)
          })
        }

        // inicializa votosMap para todos los partidos
        this.partidos.forEach(p => {
          if (this.votosMap[p.id] == null) this.votosMap[p.id] = 0
        })

        this.dlgResultado = true
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar resultado')
      } finally {
        this.saving = false
      }
    },

    async saveResultado () {
      if (!this.resMesa?.id) return
      this.saving = true
      try {
        const votos = (this.partidos || []).map(p => ({
          partido_id: p.id,
          votos: Number(this.votosMap[p.id] || 0)
        }))

        await this.$axios.put(`admin/mesas/${this.resMesa.id}/resultado`, {
          ...this.resForm,
          total_validos: this.sumVotos, // auto
          votos
        })

        this.$alert.success('Resultado guardado')
        this.dlgResultado = false
        this.refresh()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>
