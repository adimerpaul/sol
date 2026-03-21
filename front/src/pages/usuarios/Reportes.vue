<template>
  <q-page class="q-pa-md">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="row items-center q-mb-md q-gutter-sm">
      <q-icon name="assessment" color="primary" size="30px" />
      <div class="text-h6 text-weight-bold">Reportes</div>
      <q-space />
      <q-btn flat round dense icon="refresh" color="primary" :loading="loading" @click="cargar" />
    </div>

    <!-- ── Tab bar ────────────────────────────────────────────────── -->
    <q-card flat bordered>
      <q-tabs
        v-model="tab"
        dense
        align="left"
        active-color="white"
        active-bg-color="primary"
        indicator-color="transparent"
        no-caps
        class="bg-grey-1 text-grey-8"
      >
        <!-- 1 -->
        <q-tab name="del_asignados" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="how_to_reg" />
            <span>Delegados asignados en una mesa (ocupados)</span>
            <q-badge color="blue" rounded>{{ delegadosAsig.length }}</q-badge>
          </div>
        </q-tab>

        <!-- 2 -->
        <q-tab name="jef_asignados" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="supervisor_account" />
            <span>Jefes de recinto asignados</span>
            <q-badge color="teal" rounded>{{ jefesAsig.length }}</q-badge>
          </div>
        </q-tab>

        <!-- 3 -->
        <q-tab name="del_libres" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="person_off" />
            <span>Delegados de mesa libres</span>
            <q-badge color="orange" rounded>{{ delegadosLib.length }}</q-badge>
          </div>
        </q-tab>

        <!-- 4 -->
        <q-tab name="jef_libres" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="manage_accounts" />
            <span>Jefes de recinto libres</span>
            <q-badge color="deep-purple" rounded>{{ jefesLib.length }}</q-badge>
          </div>
        </q-tab>

        <!-- 5 -->
        <q-tab name="rec_sin_jefe" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="location_off" />
            <span>Recintos sin jefe asignado</span>
            <q-badge color="negative" rounded>{{ recintos.length }}</q-badge>
          </div>
        </q-tab>

        <q-tab name="mesas_libres" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="table_restaurant" />
            <span>Mesas libres</span>
            <q-badge color="brown" rounded>{{ mesasLibres.length }}</q-badge>
          </div>
        </q-tab>
      </q-tabs>

      <q-separator />

      <!-- ── Panels ──────────────────────────────────────────────── -->
      <q-tab-panels v-model="tab" animated>

        <!-- 1. Delegados Asignados -->
        <q-tab-panel name="del_asignados" class="q-pa-none">
          <PanelReporte
            titulo="Delegados asignados en una mesa (ocupados)"
            icono="how_to_reg"
            color="blue"
            :loading="loading"
            :loading-export="loadingExport.del_asignados"
            @export="exportar('del_asignados')"
          >
            <q-table flat dense :rows="delegadosAsig" :columns="colsDelegadosAsig"
              row-key="ci" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

        <!-- 2. Jefes Asignados -->
        <q-tab-panel name="jef_asignados" class="q-pa-none">
          <PanelReporte
            titulo="Jefes de recinto asignados"
            icono="supervisor_account"
            color="teal"
            :loading="loading"
            :loading-export="loadingExport.jef_asignados"
            @export="exportar('jef_asignados')"
          >
            <q-table flat dense :rows="jefesAsig" :columns="colsJefesAsig"
              row-key="ci" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

        <!-- 3. Delegados Libres -->
        <q-tab-panel name="del_libres" class="q-pa-none">
          <PanelReporte
            titulo="Delegados de mesa libres"
            icono="person_off"
            color="orange"
            :loading="loading"
            :loading-export="loadingExport.del_libres"
            @export="exportar('del_libres')"
          >
            <q-table flat dense :rows="delegadosLib" :columns="colsPersona"
              row-key="ci" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

        <!-- 4. Jefes Libres -->
        <q-tab-panel name="jef_libres" class="q-pa-none">
          <PanelReporte
            titulo="Jefes de recinto libres"
            icono="manage_accounts"
            color="deep-purple"
            :loading="loading"
            :loading-export="loadingExport.jef_libres"
            @export="exportar('jef_libres')"
          >
            <q-table flat dense :rows="jefesLib" :columns="colsPersona"
              row-key="ci" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

        <!-- 5. Recintos sin Jefe -->
        <q-tab-panel name="rec_sin_jefe" class="q-pa-none">
          <PanelReporte
            titulo="Recintos sin jefe asignado"
            icono="location_off"
            color="negative"
            :loading="loading"
            :loading-export="loadingExport.rec_sin_jefe"
            @export="exportar('rec_sin_jefe')"
          >
            <q-table flat dense :rows="recintos" :columns="colsRecintos"
              row-key="id_recinto" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="mesas_libres" class="q-pa-none">
          <PanelReporte
            titulo="Mesas libres"
            icono="table_restaurant"
            color="brown"
            :loading="loading"
            :loading-export="loadingExport.mesas_libres"
            @export="exportar('mesas_libres')"
          >
            <q-table flat dense :rows="mesasLibres" :columns="colsMesasLibres"
              row-key="mesa_key" :pagination="pagination" no-data-label="Sin datos" rows-per-page-label="Filas" />
          </PanelReporte>
        </q-tab-panel>

      </q-tab-panels>
    </q-card>

  </q-page>
</template>

<script setup>
import { ref, onMounted, getCurrentInstance, defineComponent, h } from 'vue'
import { QInnerLoading, QBtn, QIcon } from 'quasar'

const { proxy } = getCurrentInstance()

// ── Componente interno reutilizable ───────────────────────────────────────────
const PanelReporte = defineComponent({
  props: {
    titulo: String,
    icono: String,
    color: String,
    loading: Boolean,
    loadingExport: Boolean,
  },
  emits: ['export'],
  setup (props, { slots, emit }) {
    return () => [
      h('div', {
        class: `row items-center justify-between q-px-md q-py-sm bg-${props.color}-1`,
      }, [
        h('div', { class: `text-subtitle2 text-weight-medium text-${props.color}-9 row items-center q-gutter-xs` }, [
          h(QIcon, { name: props.icono }),
          h('span', props.titulo),
        ]),
        h(QBtn, {
          unelevated: true, noCaps: true, dense: true,
          icon: 'download', label: 'Exportar Excel',
          color: 'positive', size: 'sm',
          loading: props.loadingExport,
          onClick: () => emit('export'),
        }),
      ]),
      h('div', { style: 'border-top: 1px solid #e0e0e0' }),
      props.loading
        ? h(QInnerLoading, { showing: true, color: props.color })
        : slots.default?.(),
    ]
  }
})

// ── State ─────────────────────────────────────────────────────────────────────
const tab        = ref('del_asignados')
const loading    = ref(false)
const loadingExport = ref({
  del_asignados: false,
  jef_asignados: false,
  del_libres:    false,
  jef_libres:    false,
  rec_sin_jefe:  false,
  mesas_libres:  false,
})

const delegadosAsig = ref([])
const jefesAsig     = ref([])
const delegadosLib  = ref([])
const jefesLib      = ref([])
const recintos      = ref([])
const mesasLibres   = ref([])

const pagination = { rowsPerPage: 15 }

// ── Columnas ─────────────────────────────────────────────────────────────────
const colsDelegadosAsig = [
  { name: 'nro_recinto',      label: 'Nro',        field: 'nro_recinto',      align: 'center', sortable: true },
  { name: 'recinto',          label: 'Recinto',     field: 'recinto',          align: 'left',   sortable: true },
  { name: 'numero_mesa',      label: 'Mesa',        field: 'numero_mesa',      align: 'center', sortable: true },
  { name: 'nombres',          label: 'Nombres',     field: 'nombres',          align: 'left',   sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left',   sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left',   sortable: true },
  { name: 'ci',               label: 'CI',          field: 'ci',               align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.',  field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular',          label: 'Celular',     field: 'celular',          align: 'center' },
  { name: 'bloque',           label: 'Bloque',      field: 'bloque',           align: 'center' },
  { name: 'registrado_por',   label: 'Reg. por',    field: 'registrado_por',   align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
]

const colsJefesAsig = [
  { name: 'nro_recinto',      label: 'Nro',        field: 'nro_recinto',      align: 'center', sortable: true },
  { name: 'recinto',          label: 'Recinto',     field: 'recinto',          align: 'left',   sortable: true },
  { name: 'nombres',          label: 'Nombres',     field: 'nombres',          align: 'left',   sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left',   sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left',   sortable: true },
  { name: 'ci',               label: 'CI',          field: 'ci',               align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.',  field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular',          label: 'Celular',     field: 'celular',          align: 'center' },
  { name: 'bloque',           label: 'Bloque',      field: 'bloque',           align: 'center' },
  { name: 'registrado_por',   label: 'Reg. por',    field: 'registrado_por',   align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
  { name: 'tipo_jefe',        label: 'Tipo',        field: 'tipo_jefe',        align: 'center', sortable: true },
]

const colsPersona = [
  { name: 'nro_recinto',      label: 'Nro',        field: 'nro_recinto',      align: 'center', sortable: true },
  { name: 'recinto',          label: 'Recinto',     field: 'recinto',          align: 'left',   sortable: true },
  { name: 'nombres',          label: 'Nombres',     field: 'nombres',          align: 'left',   sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left',   sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left',   sortable: true },
  { name: 'ci',               label: 'CI',          field: 'ci',               align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.',  field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular',          label: 'Celular',     field: 'celular',          align: 'center' },
  { name: 'bloque',           label: 'Bloque',      field: 'bloque',           align: 'center' },
  { name: 'registrado_por',   label: 'Reg. por',    field: 'registrado_por',   align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
  { name: 'estado',           label: 'Estado',      field: 'estado',           align: 'center' },
]

const colsRecintos = [
  { name: 'nro_recinto', label: 'Nro',     field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'id_recinto',  label: 'ID',      field: 'id_recinto',  align: 'center', sortable: true },
  { name: 'recinto',     label: 'Recinto', field: 'recinto',     align: 'left',   sortable: true },
]

const colsMesasLibres = [
  { name: 'nro_recinto', label: 'Nro',     field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'recinto',     label: 'Recinto', field: 'recinto',     align: 'left',   sortable: true },
  { name: 'numero_mesa', label: 'Mesa',    field: 'numero_mesa', align: 'center', sortable: true },
  { name: 'estado',      label: 'Estado',  field: 'estado',      align: 'center', sortable: true },
]

// ── Carga ─────────────────────────────────────────────────────────────────────
async function cargar () {
  loading.value = true
  try {
    const [r1, r2, r3, r4, r5, r6] = await Promise.all([
      proxy.$axios.get('/reportes/delegados-asignados'),
      proxy.$axios.get('/reportes/jefes-asignados'),
      proxy.$axios.get('/reportes/delegados-libres'),
      proxy.$axios.get('/reportes/jefes-libres'),
      proxy.$axios.get('/reportes/recintos-sin-jefe'),
      proxy.$axios.get('/reportes/mesas-libres'),
    ])
    delegadosAsig.value = r1.data
    jefesAsig.value     = r2.data
    delegadosLib.value  = r3.data
    jefesLib.value      = r4.data
    recintos.value      = r5.data
    mesasLibres.value   = r6.data
  } catch {
    proxy.$alert.error('Error al cargar los reportes.')
  } finally {
    loading.value = false
  }
}

// ── Exportar ──────────────────────────────────────────────────────────────────
const exportEndpoints = {
  del_asignados: '/reportes/export/delegados-asignados',
  jef_asignados: '/reportes/export/jefes-asignados',
  del_libres:    '/reportes/export/delegados-libres',
  jef_libres:    '/reportes/export/jefes-libres',
  rec_sin_jefe:  '/reportes/export/recintos-sin-jefe',
  mesas_libres:  '/reportes/export/mesas-libres',
}
const exportFilenames = {
  del_asignados: 'delegados_asignados.csv',
  jef_asignados: 'jefes_asignados.csv',
  del_libres:    'delegados_libres.csv',
  jef_libres:    'jefes_libres.csv',
  rec_sin_jefe:  'recintos_sin_jefe.csv',
  mesas_libres:  'mesas_libres.csv',
}

async function exportar (tipo) {
  loadingExport.value[tipo] = true
  try {
    const resp = await proxy.$axios.get(exportEndpoints[tipo], { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([resp.data]))
    const a   = document.createElement('a')
    a.href     = url
    a.download = exportFilenames[tipo]
    a.click()
    URL.revokeObjectURL(url)
  } catch {
    proxy.$alert.error('Error al exportar.')
  } finally {
    loadingExport.value[tipo] = false
  }
}

onMounted(cargar)
</script>

<style scoped>
.rounded-tab {
  border-radius: 6px 6px 0 0;
  margin: 4px 2px 0;
  min-height: 40px;
}
</style>
