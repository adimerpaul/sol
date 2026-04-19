<template>
  <q-page class="q-pa-md">
    <div class="row items-center q-mb-md q-gutter-sm">
      <q-icon name="summarize" color="primary" size="30px" />
      <div>
        <div class="text-h6 text-weight-bold">Reportes por municipio</div>
        <div class="text-caption text-grey-7">Resumen por recinto, cantidad de mesas y habilitados</div>
      </div>
      <q-space />
      <q-btn flat round dense icon="refresh" color="primary" :loading="loadingBootstrap || loadingDetalle" @click="bootstrap" />
    </div>

    <q-card flat bordered>
      <div class="q-pa-md bg-grey-1">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-select
              v-model="filters.departamento_id"
              label="Departamento"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="label"
              :options="departamentos"
              :loading="loadingBootstrap"
              @update:model-value="onDepartamentoChange"
            />
          </div>

          <div class="col-12 col-md-5">
            <q-select
              v-model="filters.municipio_id"
              label="Municipio"
              dense
              outlined
              emit-value
              map-options
              option-value="id"
              option-label="label"
              use-input
              fill-input
              hide-selected
              input-debounce="0"
              :options="municipiosFiltrados"
              :loading="loadingMunicipios"
              @filter="filterMunicipios"
            />
          </div>

          <div class="col-12 col-md-3">
            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-btn
                  unelevated
                  color="primary"
                  icon="search"
                  label="Buscar"
                  no-caps
                  class="full-width"
                  :loading="loadingDetalle"
                  @click="cargarDetalle"
                />
              </div>
              <div class="col-6">
                <q-btn
                  flat
                  color="grey-8"
                  icon="cleaning_services"
                  label="Limpiar"
                  no-caps
                  class="full-width"
                  @click="limpiar"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <q-separator />

      <div class="q-pa-md">
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-3">
            <q-card flat bordered class="summary-card">
              <div class="text-caption text-grey-7">Recintos</div>
              <div class="text-h5 text-weight-bold">{{ totals.recintos }}</div>
            </q-card>
          </div>
          <div class="col-12 col-sm-3">
            <q-card flat bordered class="summary-card">
              <div class="text-caption text-grey-7">Usuarios asignados</div>
              <div class="text-h5 text-weight-bold">{{ totals.usuarios_asignados }}</div>
            </q-card>
          </div>
          <div class="col-12 col-sm-3">
            <q-card flat bordered class="summary-card">
              <div class="text-caption text-grey-7">Mesas</div>
              <div class="text-h5 text-weight-bold">{{ totals.mesas }}</div>
            </q-card>
          </div>
          <div class="col-12 col-sm-3">
            <q-card flat bordered class="summary-card">
              <div class="text-caption text-grey-7">Inscritos o habilitados</div>
              <div class="text-h5 text-weight-bold">{{ formatNumber(totals.habilitados) }}</div>
            </q-card>
          </div>
        </div>

        <div class="row items-center q-mb-sm">
          <div>
            <div class="text-subtitle1 text-weight-medium">
              {{ provinciaNombre ? `${provinciaNombre} - Municipio de ${municipioNombre}` : 'Seleccione un municipio' }}
            </div>
            <div class="text-caption text-grey-7">
              {{ departamentoNombre || 'Sin departamento seleccionado' }}
            </div>
          </div>
          <q-space />
          <q-btn
            unelevated
            color="negative"
            icon="picture_as_pdf"
            label="Imprimir PDF"
            no-caps
            :disable="!rows.length"
            :loading="printingPdf"
            @click="imprimirPdf"
          />
        </div>

        <q-table
          flat
          bordered
          dense
          :rows="rows"
          :columns="columns"
          row-key="nro"
          :pagination="{ sortBy: 'total_mesas', descending: true, rowsPerPage: 100 }"
          :loading="loadingDetalle"
          no-data-label="Sin datos para mostrar"
          rows-per-page-label="Filas"
        >
          <template v-slot:body-cell-usuarios_asignados_texto="props">
            <q-td :props="props" class="usuarios-cell">
              <div v-if="(props.row.usuarios_asignados || []).length">
                <div
                  v-for="usuario in props.row.usuarios_asignados"
                  :key="usuario.id"
                  class="text-caption q-mb-xs"
                >
                  <span class="text-weight-medium">{{ usuario.nombre }}</span>
                  <span class="text-grey-7"> · {{ usuario.celular || 'Sin celular' }}</span>
                </div>
              </div>
              <span v-else class="text-grey-6">Sin usuarios asignados</span>
            </q-td>
          </template>
        </q-table>
      </div>
    </q-card>
  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, reactive, ref } from 'vue'

const { proxy } = getCurrentInstance()

const loadingBootstrap = ref(false)
const loadingMunicipios = ref(false)
const loadingDetalle = ref(false)
const printingPdf = ref(false)

const filters = reactive({
  departamento_id: null,
  municipio_id: null,
})

const departamentos = ref([])
const municipios = ref([])
const municipiosFiltrados = ref([])

const departamento = ref(null)
const provincia = ref(null)
const municipio = ref(null)
const rows = ref([])
const totals = reactive({
  recintos: 0,
  usuarios_asignados: 0,
  mesas: 0,
  habilitados: 0,
})

const columns = [
  { name: 'nro', label: 'N°', field: 'nro', align: 'center', sortable: true },
  { name: 'municipio', label: 'Municipio', field: 'municipio', align: 'left', sortable: true },
  { name: 'localidad', label: 'Localidad', field: 'localidad', align: 'left', sortable: true },
  { name: 'recinto_nombre', label: 'Nombre de Recinto', field: 'recinto_nombre', align: 'left', sortable: true },
  {
    name: 'usuarios_asignados_texto',
    label: 'Usuarios asignados',
    field: row => row.usuarios_asignados_texto || 'Sin usuarios asignados',
    align: 'left',
  },
  { name: 'total_mesas', label: '# Mesas', field: 'total_mesas', align: 'center', sortable: true },
  {
    name: 'total_habilitados',
    label: 'Inscritos o habilitados',
    field: row => formatNumber(row.total_habilitados),
    align: 'right',
    sortable: true,
  },
]

const departamentoNombre = computed(() => departamento.value?.nombre || '')
const provinciaNombre = computed(() => provincia.value?.nombre || '')
const municipioNombre = computed(() => municipio.value?.nombre || '')

function formatNumber (value) {
  return Number(value || 0).toLocaleString('es-BO')
}

function resetDetalle () {
  departamento.value = null
  provincia.value = null
  municipio.value = null
  rows.value = []
  totals.recintos = 0
  totals.usuarios_asignados = 0
  totals.mesas = 0
  totals.habilitados = 0
}

function setMunicipios (data) {
  municipios.value = data || []
  municipiosFiltrados.value = [...municipios.value]
}

function filterMunicipios (value, update) {
  update(() => {
    const needle = String(value || '').trim().toLowerCase()
    municipiosFiltrados.value = !needle
      ? [...municipios.value]
      : municipios.value.filter(option => String(option.label || '').toLowerCase().includes(needle))
  })
}

async function cargarMunicipios () {
  if (!filters.departamento_id) {
    setMunicipios([])
    return
  }

  loadingMunicipios.value = true
  try {
    const { data } = await proxy.$axios.get('/reportes-municipio/municipios', {
      params: { departamento_id: filters.departamento_id },
    })
    setMunicipios(data)
  } catch {
    setMunicipios([])
    proxy.$alert.error('No se pudieron cargar los municipios.')
  } finally {
    loadingMunicipios.value = false
  }
}

async function bootstrap () {
  loadingBootstrap.value = true
  try {
    const { data } = await proxy.$axios.get('/reportes-municipio/bootstrap')
    departamentos.value = data.departamentos || []
    filters.departamento_id = data.default_departamento_id || null
    await cargarMunicipios()
    resetDetalle()
  } catch {
    proxy.$alert.error('No se pudo cargar el reporte por municipio.')
  } finally {
    loadingBootstrap.value = false
  }
}

async function onDepartamentoChange () {
  filters.municipio_id = null
  resetDetalle()
  await cargarMunicipios()
}

async function cargarDetalle () {
  if (!filters.departamento_id || !filters.municipio_id) {
    proxy.$alert.error('Seleccione departamento y municipio.')
    return
  }

  loadingDetalle.value = true
  try {
    const { data } = await proxy.$axios.get('/reportes-municipio/detalle', {
      params: {
        departamento_id: filters.departamento_id,
        municipio_id: filters.municipio_id,
      },
    })

    departamento.value = data.departamento || null
    provincia.value = data.provincia || null
    municipio.value = data.municipio || null
    rows.value = data.rows || []
    totals.recintos = data.totals?.recintos || 0
    totals.usuarios_asignados = data.totals?.usuarios_asignados || 0
    totals.mesas = data.totals?.mesas || 0
    totals.habilitados = data.totals?.habilitados || 0
  } catch (error) {
    resetDetalle()
    proxy.$alert.error(error.response?.data?.message || 'No se pudo cargar el detalle del reporte.')
  } finally {
    loadingDetalle.value = false
  }
}

async function imprimirPdf () {
  if (!filters.departamento_id || !filters.municipio_id) {
    proxy.$alert.error('Seleccione departamento y municipio.')
    return
  }

  printingPdf.value = true
  try {
    const response = await proxy.$axios.get('/reportes-municipio/pdf', {
      params: {
        departamento_id: filters.departamento_id,
        municipio_id: filters.municipio_id,
      },
      responseType: 'blob',
    })

    const file = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(file)
    window.open(url, '_blank', 'noopener,noreferrer')
    window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudo generar el PDF.')
  } finally {
    printingPdf.value = false
  }
}

async function limpiar () {
  filters.municipio_id = null
  resetDetalle()
  await cargarMunicipios()
}

onMounted(bootstrap)
</script>

<style scoped>
.summary-card {
  padding: 14px 16px;
  min-height: 96px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.usuarios-cell {
  min-width: 280px;
  white-space: normal;
}
</style>
