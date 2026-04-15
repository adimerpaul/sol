<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md q-gutter-sm">
      <q-icon name="assessment" color="primary" size="30px" />
      <div class="text-h6 text-weight-bold">Reportes</div>
      <q-space />
      <q-btn flat round dense icon="refresh" color="primary" :loading="loading" @click="cargar" />
    </div>

    <q-card flat bordered>
      <div class="q-pa-md bg-grey-1">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filtros.recinto_id"
              label="Recinto"
              dense
              outlined
              clearable
              emit-value
              map-options
              option-value="id"
              option-label="label"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="optionsFiltradas.recintos"
              @filter="(val, update) => filterOptions('recintos', val, update)"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-select
              v-model="filtros.persona_id"
              label="Persona"
              dense
              outlined
              clearable
              emit-value
              map-options
              option-value="id"
              option-label="label"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="optionsFiltradas.personas"
              @filter="(val, update) => filterOptions('personas', val, update)"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-select
              v-model="filtros.jefe_recinto_id"
              label="Jefe de recinto"
              dense
              outlined
              clearable
              emit-value
              map-options
              option-value="id"
              option-label="label"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="optionsFiltradas.jefes"
              @filter="(val, update) => filterOptions('jefes', val, update)"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-select
              v-model="filtros.delegado_mesa_id"
              label="Delegado de mesa"
              dense
              outlined
              clearable
              emit-value
              map-options
              option-value="id"
              option-label="label"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="optionsFiltradas.delegados"
              @filter="(val, update) => filterOptions('delegados', val, update)"
            />
          </div>
        </div>

        <div class="row justify-end q-gutter-sm q-mt-md">
          <q-btn flat no-caps label="Limpiar" color="grey-8" @click="limpiarFiltros" />
          <q-btn unelevated no-caps label="Buscar" color="primary" icon="search" :loading="loading" @click="cargar" />
        </div>
      </div>

      <q-separator />

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
        <q-tab name="del_asignados" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="how_to_reg" />
            <span>Delegados asignados en una mesa (ocupados)</span>
            <q-badge color="blue" rounded>{{ delegadosAsig.length }}</q-badge>
          </div>
        </q-tab>

        <q-tab name="jef_asignados" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="supervisor_account" />
            <span>Jefes de recinto asignados</span>
            <q-badge color="teal" rounded>{{ jefesAsig.length }}</q-badge>
          </div>
        </q-tab>

        <q-tab name="del_libres" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="person_off" />
            <span>Delegados de mesa libres</span>
            <q-badge color="orange" rounded>{{ delegadosLib.length }}</q-badge>
          </div>
        </q-tab>

        <q-tab name="jef_libres" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="manage_accounts" />
            <span>Jefes de recinto libres</span>
            <q-badge color="deep-purple" rounded>{{ jefesLib.length }}</q-badge>
          </div>
        </q-tab>

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

        <q-tab name="prov_sin_delegado" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="wrong_location" />
            <span>Provincias - Sin delegado</span>
            <q-badge color="red-8" rounded>{{ provSinDelegado.length }}</q-badge>
          </div>
        </q-tab>

        <q-tab name="prov_con_delegado" class="rounded-tab">
          <div class="row items-center no-wrap q-gutter-xs">
            <q-icon name="where_to_vote" />
            <span>Provincias - Con delegado</span>
            <q-badge color="green-8" rounded>{{ provConDelegado.length }}</q-badge>
          </div>
        </q-tab>
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated>
        <q-tab-panel name="del_asignados" class="q-pa-none">
          <PanelReporte
            titulo="Delegados asignados en una mesa (ocupados)"
            icono="how_to_reg"
            color="blue"
            :loading="loading"
            :loading-export="loadingExport.del_asignados"
            @export="exportar('del_asignados')"
          >
            <q-table
              flat
              dense
              :rows="delegadosAsig"
              :columns="colsDelegadosAsig"
              row-key="ci"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="jef_asignados" class="q-pa-none">
          <PanelReporte
            titulo="Jefes de recinto asignados"
            icono="supervisor_account"
            color="teal"
            :loading="loading"
            :loading-export="loadingExport.jef_asignados"
            @export="exportar('jef_asignados')"
          >
            <q-table
              flat
              dense
              :rows="jefesAsig"
              :columns="colsJefesAsig"
              row-key="ci"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="del_libres" class="q-pa-none">
          <PanelReporte
            titulo="Delegados de mesa libres"
            icono="person_off"
            color="orange"
            :loading="loading"
            :loading-export="loadingExport.del_libres"
            @export="exportar('del_libres')"
          >
            <q-table
              flat
              dense
              :rows="delegadosLib"
              :columns="colsPersona"
              row-key="ci"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="jef_libres" class="q-pa-none">
          <PanelReporte
            titulo="Jefes de recinto libres"
            icono="manage_accounts"
            color="deep-purple"
            :loading="loading"
            :loading-export="loadingExport.jef_libres"
            @export="exportar('jef_libres')"
          >
            <q-table
              flat
              dense
              :rows="jefesLib"
              :columns="colsPersona"
              row-key="ci"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="rec_sin_jefe" class="q-pa-none">
          <PanelReporte
            titulo="Recintos sin jefe asignado"
            icono="location_off"
            color="negative"
            :loading="loading"
            :loading-export="loadingExport.rec_sin_jefe"
            @export="exportar('rec_sin_jefe')"
          >
            <q-table
              flat
              dense
              :rows="recintos"
              :columns="colsRecintos"
              row-key="id_recinto"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
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
            <q-table
              flat
              dense
              :rows="mesasLibres"
              :columns="colsMesasLibres"
              row-key="mesa_key"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="prov_sin_delegado" class="q-pa-none">
          <PanelReporte
            titulo="Provincias - Mesas sin delegado"
            icono="wrong_location"
            color="red-8"
            :loading="loading"
            :loading-export="loadingExport.prov_sin_delegado"
            @export="exportar('prov_sin_delegado')"
          >
            <q-table
              flat
              dense
              :rows="provSinDelegado"
              :columns="colsProvSinDelegado"
              row-key="prov_key"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>

        <q-tab-panel name="prov_con_delegado" class="q-pa-none">
          <PanelReporte
            titulo="Provincias - Mesas con delegado asignado"
            icono="where_to_vote"
            color="green-8"
            :loading="loading"
            :loading-export="loadingExport.prov_con_delegado"
            @export="exportar('prov_con_delegado')"
          >
            <q-table
              flat
              dense
              :rows="provConDelegado"
              :columns="colsProvConDelegado"
              row-key="prov_key"
              :pagination="pagination"
              no-data-label="Sin datos"
              rows-per-page-label="Filas"
            />
          </PanelReporte>
        </q-tab-panel>
      </q-tab-panels>
    </q-card>
  </q-page>
</template>

<script setup>
import { defineComponent, getCurrentInstance, h, onMounted, reactive, ref } from 'vue'
import { QBtn, QIcon, QInnerLoading } from 'quasar'

const { proxy } = getCurrentInstance()

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
          unelevated: true,
          noCaps: true,
          dense: true,
          icon: 'download',
          label: 'Exportar Excel',
          color: 'positive',
          size: 'sm',
          loading: props.loadingExport,
          onClick: () => emit('export'),
        }),
      ]),
      h('div', { style: 'border-top: 1px solid #e0e0e0' }),
      props.loading
        ? h(QInnerLoading, { showing: true, color: props.color })
        : slots.default?.(),
    ]
  },
})

const tab = ref('del_asignados')
const loading = ref(false)
const loadingExport = ref({
  del_asignados: false,
  jef_asignados: false,
  del_libres: false,
  jef_libres: false,
  rec_sin_jefe: false,
  mesas_libres: false,
  prov_sin_delegado: false,
  prov_con_delegado: false,
})

const filtros = reactive({
  recinto_id: null,
  persona_id: null,
  jefe_recinto_id: null,
  delegado_mesa_id: null,
})

const optionsBase = reactive({
  recintos: [],
  personas: [],
  jefes: [],
  delegados: [],
})

const optionsFiltradas = reactive({
  recintos: [],
  personas: [],
  jefes: [],
  delegados: [],
})

const delegadosAsig = ref([])
const jefesAsig = ref([])
const delegadosLib = ref([])
const jefesLib = ref([])
const recintos = ref([])
const mesasLibres = ref([])
const provSinDelegado = ref([])
const provConDelegado = ref([])

const pagination = { rowsPerPage: 15 }

const colsDelegadosAsig = [
  { name: 'nro_recinto', label: 'Nro', field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'numero_mesa', label: 'Mesa', field: 'numero_mesa', align: 'center', sortable: true },
  { name: 'nombres', label: 'Nombres', field: 'nombres', align: 'left', sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left', sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left', sortable: true },
  { name: 'ci', label: 'CI', field: 'ci', align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.', field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular', label: 'Celular', field: 'celular', align: 'center' },
  { name: 'bloque', label: 'Bloque', field: 'bloque', align: 'center' },
  { name: 'registrado_por', label: 'Reg. por', field: 'registrado_por', align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
]

const colsJefesAsig = [
  { name: 'nro_recinto', label: 'Nro', field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'nombres', label: 'Nombres', field: 'nombres', align: 'left', sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left', sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left', sortable: true },
  { name: 'ci', label: 'CI', field: 'ci', align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.', field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular', label: 'Celular', field: 'celular', align: 'center' },
  { name: 'bloque', label: 'Bloque', field: 'bloque', align: 'center' },
  { name: 'registrado_por', label: 'Reg. por', field: 'registrado_por', align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
  { name: 'tipo_jefe', label: 'Tipo', field: 'tipo_jefe', align: 'center', sortable: true },
]

const colsPersona = [
  { name: 'nro_recinto', label: 'Nro', field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'nombres', label: 'Nombres', field: 'nombres', align: 'left', sortable: true },
  { name: 'apellido_paterno', label: 'Ap. Paterno', field: 'apellido_paterno', align: 'left', sortable: true },
  { name: 'apellido_materno', label: 'Ap. Materno', field: 'apellido_materno', align: 'left', sortable: true },
  { name: 'ci', label: 'CI', field: 'ci', align: 'center', sortable: true },
  { name: 'fecha_nacimiento', label: 'Fecha Nac.', field: 'fecha_nacimiento', align: 'center', sortable: true },
  { name: 'celular', label: 'Celular', field: 'celular', align: 'center' },
  { name: 'bloque', label: 'Bloque', field: 'bloque', align: 'center' },
  { name: 'registrado_por', label: 'Reg. por', field: 'registrado_por', align: 'left' },
  { name: 'registrado_en_fecha', label: 'Reg. en', field: 'registrado_en_fecha', align: 'center' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
]

const colsRecintos = [
  { name: 'nro_recinto', label: 'Nro', field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'id_recinto', label: 'ID', field: 'id_recinto', align: 'center', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
]

const colsMesasLibres = [
  { name: 'nro_recinto', label: 'Nro', field: 'nro_recinto', align: 'center', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'numero_mesa', label: 'Mesa', field: 'numero_mesa', align: 'center', sortable: true },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center', sortable: true },
]

const colsProvSinDelegado = [
  { name: 'provincia', label: 'Provincia', field: 'provincia', align: 'left', sortable: true },
  { name: 'municipio', label: 'Municipio', field: 'municipio', align: 'left', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'numero_mesa', label: 'Mesa', field: 'numero_mesa', align: 'center', sortable: true },
]

const colsProvConDelegado = [
  { name: 'provincia', label: 'Provincia', field: 'provincia', align: 'left', sortable: true },
  { name: 'municipio', label: 'Municipio', field: 'municipio', align: 'left', sortable: true },
  { name: 'recinto', label: 'Recinto', field: 'recinto', align: 'left', sortable: true },
  { name: 'numero_mesa', label: 'Mesa', field: 'numero_mesa', align: 'center', sortable: true },
  { name: 'delegado', label: 'Delegado', field: 'delegado', align: 'left', sortable: true },
]

function buildParams () {
  const params = {}

  if (filtros.recinto_id) params.recinto_id = filtros.recinto_id
  if (filtros.persona_id) params.persona_id = filtros.persona_id
  if (filtros.jefe_recinto_id) params.jefe_recinto_id = filtros.jefe_recinto_id
  if (filtros.delegado_mesa_id) params.delegado_mesa_id = filtros.delegado_mesa_id

  return params
}

function hydrateOptions (filters = {}) {
  optionsBase.recintos = filters.recintos || []
  optionsBase.personas = filters.personas || []
  optionsBase.jefes = filters.jefes || []
  optionsBase.delegados = filters.delegados || []

  optionsFiltradas.recintos = [...optionsBase.recintos]
  optionsFiltradas.personas = [...optionsBase.personas]
  optionsFiltradas.jefes = [...optionsBase.jefes]
  optionsFiltradas.delegados = [...optionsBase.delegados]
}

function filterOptions (key, value, update) {
  const source = optionsBase[key] || []
  update(() => {
    const needle = String(value || '').trim().toLowerCase()
    optionsFiltradas[key] = !needle
      ? [...source]
      : source.filter(option => String(option.label || '').toLowerCase().includes(needle))
  })
}

async function cargar () {
  loading.value = true
  try {
    const { data } = await proxy.$axios.get('/reportes/bootstrap', {
      params: buildParams(),
    })

    hydrateOptions(data.filters || {})

    delegadosAsig.value = data.data?.del_asignados || []
    jefesAsig.value = data.data?.jef_asignados || []
    delegadosLib.value = data.data?.del_libres || []
    jefesLib.value = data.data?.jef_libres || []
    recintos.value = data.data?.rec_sin_jefe || []
    mesasLibres.value = data.data?.mesas_libres || []
    provSinDelegado.value = data.data?.prov_sin_delegado || []
    provConDelegado.value = data.data?.prov_con_delegado || []
  } catch {
    proxy.$alert.error('Error al cargar los reportes.')
  } finally {
    loading.value = false
  }
}

function limpiarFiltros () {
  filtros.recinto_id = null
  filtros.persona_id = null
  filtros.jefe_recinto_id = null
  filtros.delegado_mesa_id = null
  cargar()
}

const exportConfigs = {
  del_asignados: {
    data: () => delegadosAsig.value,
    filename: 'delegados_asignados',
    sheet: 'Delegados Asignados',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Mesa', value: 'numero_mesa' },
      { label: 'Nombres', value: 'nombres' },
      { label: 'Ap. Paterno', value: 'apellido_paterno' },
      { label: 'Ap. Materno', value: 'apellido_materno' },
      { label: 'CI', value: 'ci' },
      { label: 'Fecha Nac.', value: 'fecha_nacimiento' },
      { label: 'Celular', value: 'celular' },
      { label: 'Bloque', value: 'bloque' },
      { label: 'Reg. por', value: 'registrado_por' },
      { label: 'Reg. en', value: 'registrado_en_fecha' },
    ],
  },
  jef_asignados: {
    data: () => jefesAsig.value,
    filename: 'jefes_asignados',
    sheet: 'Jefes Asignados',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Nombres', value: 'nombres' },
      { label: 'Ap. Paterno', value: 'apellido_paterno' },
      { label: 'Ap. Materno', value: 'apellido_materno' },
      { label: 'CI', value: 'ci' },
      { label: 'Fecha Nac.', value: 'fecha_nacimiento' },
      { label: 'Celular', value: 'celular' },
      { label: 'Bloque', value: 'bloque' },
      { label: 'Reg. por', value: 'registrado_por' },
      { label: 'Reg. en', value: 'registrado_en_fecha' },
      { label: 'Tipo', value: 'tipo_jefe' },
    ],
  },
  del_libres: {
    data: () => delegadosLib.value,
    filename: 'delegados_libres',
    sheet: 'Delegados Libres',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Nombres', value: 'nombres' },
      { label: 'Ap. Paterno', value: 'apellido_paterno' },
      { label: 'Ap. Materno', value: 'apellido_materno' },
      { label: 'CI', value: 'ci' },
      { label: 'Fecha Nac.', value: 'fecha_nacimiento' },
      { label: 'Celular', value: 'celular' },
      { label: 'Bloque', value: 'bloque' },
      { label: 'Reg. por', value: 'registrado_por' },
      { label: 'Reg. en', value: 'registrado_en_fecha' },
      { label: 'Estado', value: 'estado' },
    ],
  },
  jef_libres: {
    data: () => jefesLib.value,
    filename: 'jefes_libres',
    sheet: 'Jefes Libres',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Nombres', value: 'nombres' },
      { label: 'Ap. Paterno', value: 'apellido_paterno' },
      { label: 'Ap. Materno', value: 'apellido_materno' },
      { label: 'CI', value: 'ci' },
      { label: 'Fecha Nac.', value: 'fecha_nacimiento' },
      { label: 'Celular', value: 'celular' },
      { label: 'Bloque', value: 'bloque' },
      { label: 'Reg. por', value: 'registrado_por' },
      { label: 'Reg. en', value: 'registrado_en_fecha' },
      { label: 'Estado', value: 'estado' },
    ],
  },
  rec_sin_jefe: {
    data: () => recintos.value,
    filename: 'recintos_sin_jefe',
    sheet: 'Recintos Sin Jefe',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'ID Recinto', value: 'id_recinto' },
      { label: 'Recinto', value: 'recinto' },
    ],
  },
  mesas_libres: {
    data: () => mesasLibres.value,
    filename: 'mesas_libres',
    sheet: 'Mesas Libres',
    columns: [
      { label: 'Nro', value: 'nro_recinto' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Mesa', value: 'numero_mesa' },
      { label: 'Estado', value: 'estado' },
    ],
  },
  prov_sin_delegado: {
    data: () => provSinDelegado.value,
    filename: 'provincias_sin_delegado',
    sheet: 'Prov. Sin Delegado',
    columns: [
      { label: 'Provincia', value: 'provincia' },
      { label: 'Municipio', value: 'municipio' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Mesa', value: 'numero_mesa' },
    ],
  },
  prov_con_delegado: {
    data: () => provConDelegado.value,
    filename: 'provincias_con_delegado',
    sheet: 'Prov. Con Delegado',
    columns: [
      { label: 'Provincia', value: 'provincia' },
      { label: 'Municipio', value: 'municipio' },
      { label: 'Recinto', value: 'recinto' },
      { label: 'Mesa', value: 'numero_mesa' },
      { label: 'Delegado', value: 'delegado' },
    ],
  },
}

async function exportar (tipo) {
  loadingExport.value[tipo] = true
  try {
    const config = exportConfigs[tipo]
    if (!config) throw new Error('Tipo no válido')

    const rows = config.data()
    if (!rows.length) {
      proxy.$alert.error('No hay datos para exportar')
      return
    }

    const { Excel } = await import('src/addons/Excel')
    const content = rows.map(row => {
      const obj = {}
      for (const col of config.columns) {
        obj[col.label] = row[col.value] ?? ''
      }
      return obj
    })

    const data = [{
      sheet: config.sheet,
      columns: config.columns.map(c => ({ label: c.label, value: c.label })),
      content,
    }]

    const date = new Date().toISOString().slice(0, 10)
    Excel.export(data, `${config.filename}_${date}`)
    proxy.$alert.success('Excel generado')
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
