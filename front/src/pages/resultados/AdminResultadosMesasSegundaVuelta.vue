<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section class="row q-col-gutter-sm items-start">
        <div class="col-12 col-md-4">
          <div class="text-h6 text-weight-bold">Resultados Mesas Segunda Vuelta</div>
          <div class="text-caption text-grey-7">
            Oruro completo. Asignacion masiva y carga simplificada de gobernador.
          </div>
        </div>

        <div class="col-12 col-md-8">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.provincia_id" :options="provinciaOptions" option-label="label" option-value="value" emit-value map-options clearable dense outlined label="Provincia" @update:model-value="onProvinciaChange" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.municipio_id" :options="municipioOptions" option-label="label" option-value="value" emit-value map-options clearable dense outlined label="Municipio" :disable="!filters.provincia_id" @update:model-value="onMunicipioChange" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.localidad_id" :options="localidadOptions" option-label="label" option-value="value" emit-value map-options clearable dense outlined label="Localidad" :disable="!filters.municipio_id" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.recinto_id" :options="recintoOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="150" clearable dense outlined label="Recinto" @filter="filterRecintos" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.delegado_id" :options="delegadoOptionsFiltered" option-label="label" option-value="value" emit-value map-options use-input input-debounce="150" clearable dense outlined label="Delegado 2V" @filter="filterDelegados" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.estado" :options="estadoOptions" clearable dense outlined label="Estado 2V" />
            </div>
            <div class="col-12 col-sm-3">
              <q-select v-model="filters.con_resultado" :options="resultadoOptions" clearable dense outlined label="Resultado" />
            </div>
            <div class="col-12 col-sm-3">
              <q-input v-model="filters.search" dense outlined clearable label="Buscar mesa/recinto">
                <template #append><q-icon name="search" /></template>
              </q-input>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pt-sm q-pb-none">
        <div class="row q-col-gutter-sm items-center">
          <div class="col-auto"><q-chip outline color="primary">Oruro: {{ rows.length }} mesas</q-chip></div>
          <div class="col-auto"><q-chip outline color="secondary">Filtradas: {{ filteredRows.length }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="positive">Asignadas: {{ filteredAssignedCount }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="negative">Sin delegado: {{ filteredRows.length - filteredAssignedCount }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="teal">Con resultado: {{ filteredResultCount }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="indigo">Seleccionadas: {{ selected.length }}</q-chip></div>
          <q-space />
          <div class="col-auto"><q-btn flat color="grey-7" icon="filter_alt_off" label="Limpiar filtros" no-caps @click="clearFilters" /></div>
          <div class="col-auto"><q-btn color="primary" icon="refresh" label="Actualizar" no-caps :loading="loading" @click="load" /></div>
        </div>
      </q-card-section>

      <q-card-section class="q-pt-sm">
        <q-banner dense class="bg-blue-1 text-black q-mb-md">
          Usa el checkbox del encabezado para seleccionar todas las mesas filtradas.
        </q-banner>

        <div class="row q-col-gutter-sm q-mb-md">
          <div class="col-12 col-md-5">
            <q-select v-model="bulkDelegadoId" :options="bulkDelegadoOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="150" clearable dense outlined label="Delegado para seleccionadas" @filter="filterDelegadosBulk" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="bulkEstado" :options="estadoAssignOptions" dense outlined label="Estado" />
          </div>
          <div class="col-12 col-md-4 row q-col-gutter-sm">
            <div class="col-6">
              <q-btn color="positive" icon="assignment_ind" label="Asignar seleccionadas" no-caps class="full-width" :disable="!selected.length || !bulkDelegadoId" :loading="savingBulk" @click="saveBulkAssign" />
            </div>
            <div class="col-6">
              <q-btn color="negative" icon="person_off" label="Liberar seleccionadas" no-caps outline class="full-width" :disable="!selected.length" :loading="savingBulk" @click="clearBulkAssign" />
            </div>
          </div>
        </div>

        <q-table
          v-model:selected="selected"
          v-model:pagination="pagination"
          :rows="filteredRows"
          :columns="columns"
          row-key="id"
          selection="multiple"
          flat
          bordered
          dense
          :loading="loading"
          :rows-per-page-options="[50, 100, 200]"
        >
          <template #top-right>
            <q-select
              v-model="pagination.rowsPerPage"
              :options="rowsPerPageOptions"
              dense
              outlined
              emit-value
              map-options
              label="Filas"
              style="width: 120px"
            />
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props" class="text-center">
              <q-btn-dropdown dense color="primary" icon="more_horiz" no-caps>
                <q-list>
                  <q-item clickable v-close-popup @click="openAssignDialog(props.row)">
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

          <template #body-cell-mesa="props">
            <q-td :props="props">
              <div class="text-weight-bold">Mesa {{ props.row.numero_mesa }}</div>
              <div class="text-caption text-grey-7">{{ props.row.recinto_nombre }}</div>
              <div class="text-caption text-grey-6">{{ props.row.localidad_nombre || 'Sin localidad' }} · {{ props.row.municipio_nombre || 'Sin municipio' }}</div>
            </q-td>
          </template>

          <template #body-cell-delegado="props">
            <q-td :props="props">
              <div v-if="props.row.delegado_segunda_vuelta">
                <div class="text-weight-medium">{{ props.row.delegado_segunda_vuelta.name }}</div>
                <div class="text-caption text-grey-7">{{ props.row.delegado_segunda_vuelta.username }}</div>
                <div class="text-caption text-grey-7">{{ props.row.delegado_segunda_vuelta.celular || 'Sin celular' }}</div>
              </div>
              <q-badge v-else outline color="negative">SIN ASIGNAR</q-badge>
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props">
              <q-chip dense text-color="white" :color="estadoColor(props.row.estado_segunda_vuelta)">{{ props.row.estado_segunda_vuelta }}</q-chip>
            </q-td>
          </template>

          <template #body-cell-resultado="props">
            <q-td :props="props">
              <div class="row items-center q-gutter-xs">
                <q-badge outline :color="props.row.tiene_resultado ? 'teal' : 'grey-6'">{{ props.row.tiene_resultado ? 'CON RESULTADO' : 'SIN RESULTADO' }}</q-badge>
                <q-chip v-if="props.row.tiene_resultado" dense color="primary" text-color="white">Total: {{ props.row.total_votos }}</q-chip>
              </div>
              <div class="text-caption text-grey-7 q-mt-xs">{{ buildResultCaption(props.row) }}</div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <q-dialog v-model="assignDialog" persistent>
      <q-card style="width: 520px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Asignar delegado segunda vuelta</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="assignDialog = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7 q-mb-sm">{{ currentMesa?.recinto_nombre }} · Mesa {{ currentMesa?.numero_mesa }}</div>
          <q-select v-model="assignDelegadoId" :options="assignDelegadoOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="150" clearable dense outlined label="Delegado" @filter="filterAssignDelegados" />
          <q-select v-model="assignEstado" class="q-mt-sm" :options="estadoAssignOptions" dense outlined label="Estado" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="grey-7" label="Cancelar" no-caps @click="assignDialog = false" />
          <q-btn color="primary" label="Guardar" no-caps :loading="savingAssign" @click="saveAssign" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="resultadoDialog" persistent maximized>
      <q-card>
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Registro Segunda Vuelta</div>
          <q-space />
          <q-btn color="primary" label="Guardar" no-caps class="q-mr-sm" :loading="savingResult" :disable="!resultadoMesa?.delegado_segunda_vuelta_id" @click="saveResultado" />
          <q-btn icon="close" flat round dense @click="resultadoDialog = false" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7">
            {{ resultadoMesa?.recinto_nombre }} · Mesa {{ resultadoMesa?.numero_mesa }} ·
            {{ resultadoMesa?.provincia_nombre || 'Sin provincia' }} ·
            {{ resultadoMesa?.municipio_nombre || 'Sin municipio' }} ·
            Delegado 2V: <span class="text-weight-medium">{{ resultadoMesa?.delegado_segunda_vuelta?.name || 'SIN ASIGNAR' }}</span>
          </div>
          <q-banner v-if="!resultadoMesa?.delegado_segunda_vuelta_id" dense class="bg-orange-2 text-black q-mt-sm">
            No puedes registrar resultados si la mesa no tiene delegado de segunda vuelta asignado.
          </q-banner>
          <div class="row q-col-gutter-md q-mt-md">
            <div class="col-12 col-lg-7">
              <q-card flat bordered>
                <q-card-section class="text-weight-bold">Votos Gobernador</q-card-section>
                <q-separator />
                <q-card-section class="row q-col-gutter-sm">
                  <div v-for="partido in partidos" :key="partido.id" class="col-12 col-md-6">
                    <div class="party-card" :style="partyCardStyle(partido)">
                      <div class="row items-center q-col-gutter-sm q-mb-sm">
                        <div class="col-auto">
                          <q-avatar rounded size="44px" class="bg-white shadow-1">
                            <q-img v-if="partidoIconUrl(partido)" :src="partidoIconUrl(partido)" fit="contain" />
                            <q-icon v-else name="flag" />
                          </q-avatar>
                        </div>
                        <div class="col">
                          <div class="text-weight-bold">{{ partidoDisplayName(partido) }}</div>
                          <div class="text-caption">{{ partido.sigla || 'Sin sigla' }}</div>
                        </div>
                      </div>
                      <q-input v-model.number="votosMap[partido.id]" type="number" min="0" dense outlined :label="`Votos ${partidoDisplayName(partido)}`" bg-color="white" />
                    </div>
                  </div>
                </q-card-section>
              </q-card>
              <q-card flat bordered class="q-mt-md">
                <q-card-section class="text-weight-bold">Totales y observacion</q-card-section>
                <q-separator />
                <q-card-section class="row q-col-gutter-sm">
                  <div class="col-12 col-md-4"><q-input v-model.number="form.blancos" type="number" min="0" dense outlined label="Blancos" /></div>
                  <div class="col-12 col-md-4"><q-input v-model.number="form.nulos" type="number" min="0" dense outlined label="Nulos" /></div>
                  <div class="col-12 col-md-4"><q-input v-model.number="form.papeletas_no_utilizadas" type="number" min="0" dense outlined label="Papeletas no utilizadas" /></div>
                  <div class="col-12"><q-input v-model="form.observacion" type="textarea" autogrow dense outlined label="Observacion" /></div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-lg-5">
              <q-card flat bordered>
                <q-card-section class="text-weight-bold">Resumen</q-card-section>
                <q-separator />
                <q-card-section>
                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-sm-6"><q-chip color="primary" text-color="white">Validos: {{ totalValidos }}</q-chip></div>
                    <div class="col-12 col-sm-6"><q-chip color="teal" text-color="white">Total votos: {{ totalVotos }}</q-chip></div>
                  </div>
                  <div v-for="partido in partidos" :key="`sum-${partido.id}`" class="q-mt-sm"><strong>{{ partidoLabel(partido) }}:</strong> {{ votosMap[partido.id] || 0 }}</div>
                </q-card-section>
              </q-card>
              <q-card flat bordered class="q-mt-md">
                <q-card-section class="text-weight-bold">Fotos</q-card-section>
                <q-separator />
                <q-card-section class="q-gutter-md">
                  <div>
                    <div class="text-caption text-grey-7 q-mb-xs">Foto del pizarron</div>
                    <q-file v-model="fotos.foto_pizarra" dense outlined clearable accept="image/*" label="Seleccionar foto del pizarron" />
                    <div v-if="photoPreview('foto_pizarra')" class="q-mt-sm"><img :src="photoPreview('foto_pizarra')" class="photo-preview" alt="Foto pizarron" /></div>
                    <div class="q-mt-xs">
                      <q-btn flat dense no-caps color="primary" label="Abrir" :disable="!photoPreview('foto_pizarra')" @click="openPhoto('foto_pizarra')" />
                      <q-btn flat dense no-caps color="negative" label="Quitar" :disable="!photoPreview('foto_pizarra')" @click="clearPhoto('foto_pizarra')" />
                    </div>
                  </div>
                  <div>
                    <div class="text-caption text-grey-7 q-mb-xs">Foto del acta</div>
                    <q-file v-model="fotos.foto_acta" dense outlined clearable accept="image/*" label="Seleccionar foto del acta" />
                    <div v-if="photoPreview('foto_acta')" class="q-mt-sm"><img :src="photoPreview('foto_acta')" class="photo-preview" alt="Foto acta" /></div>
                    <div class="q-mt-xs">
                      <q-btn flat dense no-caps color="primary" label="Abrir" :disable="!photoPreview('foto_acta')" @click="openPhoto('foto_acta')" />
                      <q-btn flat dense no-caps color="negative" label="Quitar" :disable="!photoPreview('foto_acta')" @click="clearPhoto('foto_acta')" />
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'AdminResultadosMesasSegundaVuelta',
  data () {
    return {
      loading: false,
      savingBulk: false,
      savingAssign: false,
      savingResult: false,
      rows: [],
      geo: {
        provincias: [],
        municipios: [],
        localidades: []
      },
      partidos: [],
      delegados: [],
      delegadoOptionsAll: [],
      delegadoOptionsFiltered: [],
      bulkDelegadoOptions: [],
      recintoOptionsAll: [],
      recintoOptions: [],
      selected: [],
      bulkDelegadoId: null,
      bulkEstado: 'ASIGNADA',
      filters: {
        provincia_id: null,
        municipio_id: null,
        localidad_id: null,
        recinto_id: null,
        delegado_id: null,
        estado: null,
        con_resultado: null,
        search: ''
      },
      columns: [
        { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' },
        { name: 'mesa', label: 'Mesa', field: 'numero_mesa', align: 'left', sortable: true },
        { name: 'delegado', label: 'Delegado 2V', field: 'delegado_segunda_vuelta', align: 'left' },
        { name: 'estado', label: 'Estado 2V', field: 'estado_segunda_vuelta', align: 'left', sortable: true },
        { name: 'resultado', label: 'Resultado', field: 'total_votos', align: 'left' }
      ],
      pagination: {
        sortBy: 'numero_mesa',
        descending: false,
        page: 1,
        rowsPerPage: 100
      },
      rowsPerPageOptions: [
        { label: '50', value: 50 },
        { label: '100', value: 100 },
        { label: '200', value: 200 }
      ],
      estadoOptions: ['PENDIENTE', 'ASIGNADA', 'RESULTADO_REGISTRADO'],
      estadoAssignOptions: ['ASIGNADA', 'RESULTADO_REGISTRADO'],
      resultadoOptions: ['CON RESULTADO', 'SIN RESULTADO'],
      assignDialog: false,
      currentMesa: null,
      assignDelegadoId: null,
      assignEstado: 'ASIGNADA',
      assignDelegadoOptions: [],
      resultadoDialog: false,
      resultadoMesa: null,
      form: {
        blancos: 0,
        nulos: 0,
        papeletas_no_utilizadas: 0,
        observacion: ''
      },
      votosMap: {},
      fotos: {
        foto_pizarra: null,
        foto_acta: null
      },
      photosServer: {
        foto_pizarra_url: null,
        foto_acta_url: null
      },
      photosToClear: {
        foto_pizarra: false,
        foto_acta: false
      }
    }
  },

  computed: {
    provinciaOptions () {
      return (this.geo.provincias || []).map(item => ({ label: item.nombre, value: item.id }))
    },

    municipioOptions () {
      return (this.geo.municipios || [])
        .filter(item => !this.filters.provincia_id || item.provincia_id === this.filters.provincia_id)
        .map(item => ({ label: item.nombre, value: item.id }))
    },

    localidadOptions () {
      return (this.geo.localidades || [])
        .filter(item => !this.filters.municipio_id || item.municipio_id === this.filters.municipio_id)
        .map(item => ({ label: item.nombre, value: item.id }))
    },

    filteredRows () {
      const search = String(this.filters.search || '').trim().toLowerCase()

      return (this.rows || []).filter(row => {
        if (this.filters.provincia_id && row.provincia_id !== this.filters.provincia_id) return false
        if (this.filters.municipio_id && row.municipio_id !== this.filters.municipio_id) return false
        if (this.filters.localidad_id && row.localidad_id !== this.filters.localidad_id) return false
        if (this.filters.recinto_id && row.recinto_id !== this.filters.recinto_id) return false
        if (this.filters.delegado_id && row.delegado_segunda_vuelta_id !== this.filters.delegado_id) return false
        if (this.filters.estado && row.estado_segunda_vuelta !== this.filters.estado) return false
        if (this.filters.con_resultado === 'CON RESULTADO' && !row.tiene_resultado) return false
        if (this.filters.con_resultado === 'SIN RESULTADO' && row.tiene_resultado) return false

        if (!search) return true

        const haystack = [
          row.numero_mesa,
          row.recinto_nombre,
          row.localidad_nombre,
          row.municipio_nombre,
          row.provincia_nombre,
          row.delegado_segunda_vuelta?.name,
          row.delegado_segunda_vuelta?.username
        ].join(' ').toLowerCase()

        return haystack.includes(search)
      })
    },

    filteredAssignedCount () {
      return this.filteredRows.filter(row => !!row.delegado_segunda_vuelta_id).length
    },

    filteredResultCount () {
      return this.filteredRows.filter(row => !!row.tiene_resultado).length
    },

    totalValidos () {
      return (this.partidos || []).reduce((acc, partido) => acc + Number(this.votosMap[partido.id] || 0), 0)
    },

    totalVotos () {
      return this.totalValidos + Number(this.form.blancos || 0) + Number(this.form.nulos || 0)
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    async load () {
      this.loading = true
      try {
        const data = await this.$axios.get('admin/mesas-segunda-vuelta/bootstrap').then(r => r.data)
        this.rows = Array.isArray(data?.mesas) ? data.mesas : []
        this.geo = data?.geo || { provincias: [], municipios: [], localidades: [] }
        this.partidos = Array.isArray(data?.partidos) ? data.partidos : []
        this.delegados = Array.isArray(data?.delegados) ? data.delegados : []
        this.delegadoOptionsAll = this.buildDelegadoOptions(this.delegados)
        this.delegadoOptionsFiltered = this.delegadoOptionsAll
        this.bulkDelegadoOptions = this.delegadoOptionsAll
        this.assignDelegadoOptions = this.delegadoOptionsAll
        this.recintoOptionsAll = this.buildRecintoOptions(this.rows)
        this.recintoOptions = this.recintoOptionsAll
        this.selected = []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar segunda vuelta')
      } finally {
        this.loading = false
      }
    },

    buildDelegadoOptions (rows) {
      return (rows || []).map(item => ({
        label: `${item.name} (${item.username})${item.celular ? ` · ${item.celular}` : ''}`,
        value: item.id
      }))
    },

    buildRecintoOptions (rows) {
      return [...new Map((rows || []).map(row => [row.recinto_id, {
        label: `${row.recinto_nombre} · ${row.localidad_nombre || row.municipio_nombre || 'Sin ubicacion'}`,
        value: row.recinto_id
      }])).values()]
    },

    onProvinciaChange () {
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.filters.recinto_id = null
      this.recintoOptions = this.recintoOptionsAll.filter(opt => {
        const row = this.rows.find(item => item.recinto_id === opt.value)
        return !this.filters.provincia_id || row?.provincia_id === this.filters.provincia_id
      })
    },

    onMunicipioChange () {
      this.filters.localidad_id = null
      this.filters.recinto_id = null
      this.recintoOptions = this.recintoOptionsAll.filter(opt => {
        const row = this.rows.find(item => item.recinto_id === opt.value)
        return !this.filters.municipio_id || row?.municipio_id === this.filters.municipio_id
      })
    },

    clearFilters () {
      this.filters = {
        provincia_id: null,
        municipio_id: null,
        localidad_id: null,
        recinto_id: null,
        delegado_id: null,
        estado: null,
        con_resultado: null,
        search: ''
      }
      this.recintoOptions = this.recintoOptionsAll
      this.delegadoOptionsFiltered = this.delegadoOptionsAll
      this.bulkDelegadoOptions = this.delegadoOptionsAll
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.recintoOptions = this.recintoOptionsAll
          return
        }
        this.recintoOptions = this.recintoOptionsAll.filter(opt => String(opt.label || '').toLowerCase().includes(needle))
      })
    },

    filterDelegados (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.delegadoOptionsFiltered = this.delegadoOptionsAll
          return
        }
        this.delegadoOptionsFiltered = this.delegadoOptionsAll.filter(opt => String(opt.label || '').toLowerCase().includes(needle))
      })
    },

    filterDelegadosBulk (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.bulkDelegadoOptions = this.delegadoOptionsAll
          return
        }
        this.bulkDelegadoOptions = this.delegadoOptionsAll.filter(opt => String(opt.label || '').toLowerCase().includes(needle))
      })
    },

    filterAssignDelegados (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.assignDelegadoOptions = this.delegadoOptionsAll
          return
        }
        this.assignDelegadoOptions = this.delegadoOptionsAll.filter(opt => String(opt.label || '').toLowerCase().includes(needle))
      })
    },

    estadoColor (estado) {
      if (estado === 'RESULTADO_REGISTRADO') return 'teal'
      if (estado === 'ASIGNADA') return 'positive'
      return 'grey-7'
    },

    buildResultCaption (row) {
      if (!row.tiene_resultado) return 'Patria/Jacha aun no registrados'
      const patria = Number(row.votos_partidos?.[11]?.votos_gobernador || 0)
      const jacha = Number(row.votos_partidos?.[15]?.votos_gobernador || 0)
      return `Patria: ${patria} · Jacha: ${jacha} · B: ${row.blancos} · N: ${row.nulos}`
    },

    partidoDisplayName (partido) {
      if (Number(partido?.id) === 11) return 'Patria'
      if (Number(partido?.id) === 15) return 'Jacha'
      return partido?.nombre || `Partido ${partido?.id || ''}`
    },

    partidoIconUrl (partido) {
      if (!partido?.icono) return null
      return this.getImageUrl(`images/partidos/${partido.icono}`)
    },

    partyCardStyle (partido) {
      const color = String(partido?.color || '#d9d9d9').trim() || '#d9d9d9'
      return {
        borderTop: `4px solid ${color}`,
        background: `linear-gradient(180deg, ${this.hexToRgba(color, 0.18)} 0%, rgba(255,255,255,0.98) 72%)`
      }
    },

    hexToRgba (hex, alpha = 1) {
      const normalized = String(hex || '').replace('#', '').trim()
      if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return `rgba(217,217,217,${alpha})`
      }
      const bigint = parseInt(normalized, 16)
      const r = (bigint >> 16) & 255
      const g = (bigint >> 8) & 255
      const b = bigint & 255
      return `rgba(${r}, ${g}, ${b}, ${alpha})`
    },

    openAssignDialog (row) {
      this.currentMesa = row
      this.assignDelegadoId = row.delegado_segunda_vuelta_id || null
      this.assignEstado = row.estado_segunda_vuelta === 'RESULTADO_REGISTRADO' ? 'RESULTADO_REGISTRADO' : 'ASIGNADA'
      this.assignDelegadoOptions = this.delegadoOptionsAll
      this.assignDialog = true
    },

    async saveAssign () {
      if (!this.currentMesa?.id) return
      this.savingAssign = true
      try {
        const res = await this.$axios.put(`admin/mesas-segunda-vuelta/${this.currentMesa.id}/delegado`, {
          delegado_id: this.assignDelegadoId,
          estado: this.assignDelegadoId ? this.assignEstado : null
        })
        this.patchRow(res?.data?.row)
        this.assignDialog = false
        this.$alert.success(this.assignDelegadoId ? 'Delegado asignado' : 'Mesa liberada')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar la asignacion')
      } finally {
        this.savingAssign = false
      }
    },

    async saveBulkAssign () {
      this.savingBulk = true
      try {
        const res = await this.$axios.put('admin/mesas-segunda-vuelta/asignacion-masiva', {
          mesa_ids: this.selected.map(row => row.id),
          delegado_id: this.bulkDelegadoId,
          estado: this.bulkEstado
        })
        this.patchRows(res?.data?.rows || [])
        this.selected = []
        this.$alert.success(`${res?.data?.updated_count || 0} mesas asignadas`)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo asignar en masivo')
      } finally {
        this.savingBulk = false
      }
    },

    async clearBulkAssign () {
      this.savingBulk = true
      try {
        const res = await this.$axios.put('admin/mesas-segunda-vuelta/asignacion-masiva', {
          mesa_ids: this.selected.map(row => row.id),
          delegado_id: null
        })
        this.patchRows(res?.data?.rows || [])
        this.selected = []
        this.$alert.success(`${res?.data?.updated_count || 0} mesas liberadas`)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo liberar en masivo')
      } finally {
        this.savingBulk = false
      }
    },

    partidoLabel (partido) {
      return partido.sigla ? `${partido.sigla} · ${partido.nombre}` : (partido.nombre || `Partido ${partido.id}`)
    },

    resetResultForm () {
      this.form = {
        blancos: 0,
        nulos: 0,
        papeletas_no_utilizadas: 0,
        observacion: ''
      }
      this.votosMap = {}
      this.fotos = {
        foto_pizarra: null,
        foto_acta: null
      }
      this.photosServer = {
        foto_pizarra_url: null,
        foto_acta_url: null
      }
      this.photosToClear = {
        foto_pizarra: false,
        foto_acta: false
      }
    },

    async openResultado (row) {
      this.savingResult = true
      try {
        const data = await this.$axios.get(`admin/mesas-segunda-vuelta/${row.id}/resultado`).then(r => r.data)
        this.resultadoMesa = data?.mesa || null
        this.partidos = Array.isArray(data?.partidos) ? data.partidos : []
        this.resetResultForm()

        this.partidos.forEach(partido => {
          this.votosMap[partido.id] = 0
        })

        if (data?.resultado) {
          const resultado = data.resultado
          this.form.blancos = Number(resultado.blancos || 0)
          this.form.nulos = Number(resultado.nulos || 0)
          this.form.papeletas_no_utilizadas = Number(resultado.papeletas_no_utilizadas || 0)
          this.form.observacion = resultado.observacion || ''
          this.photosServer.foto_pizarra_url = resultado.foto_pizarra_url || null
          this.photosServer.foto_acta_url = resultado.foto_acta_url || null

          ;(resultado.detalles || []).forEach(detalle => {
            this.votosMap[detalle.partido_id] = Number(detalle.votos_gobernador || 0)
          })
        }

        this.resultadoDialog = true
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar el resultado')
      } finally {
        this.savingResult = false
      }
    },

    photoPreview (key) {
      const file = this.fotos[key]
      if (file) return URL.createObjectURL(file)
      if (this.photosToClear[key]) return null
      return this.getImageUrl(this.photosServer[`${key}_url`])
    },

    getImageUrl (path) {
      if (!path) return null
      if (String(path).startsWith('http')) return path
      const baseUrl = this.$url.split('/api')[0] || this.$url
      return baseUrl + (String(path).startsWith('/') ? '' : '/') + path
    },

    openPhoto (key) {
      const url = this.photoPreview(key)
      if (!url) return
      window.open(url, '_blank', 'noopener,noreferrer')
    },

    clearPhoto (key) {
      this.fotos[key] = null
      this.photosToClear[key] = true
      this.photosServer[`${key}_url`] = null
    },

    async saveResultado () {
      if (!this.resultadoMesa?.id) return

      this.savingResult = true
      try {
        const payloadVotos = (this.partidos || []).map(partido => ({
          partido_id: partido.id,
          votos_gobernador: Number(this.votosMap[partido.id] || 0)
        }))

        const fd = new FormData()
        fd.append('blancos', String(this.form.blancos || 0))
        fd.append('nulos', String(this.form.nulos || 0))
        fd.append('papeletas_no_utilizadas', String(this.form.papeletas_no_utilizadas || 0))
        fd.append('observacion', this.form.observacion || '')
        fd.append('votos', JSON.stringify(payloadVotos))
        if (this.fotos.foto_pizarra) fd.append('foto_pizarra', this.fotos.foto_pizarra)
        if (this.fotos.foto_acta) fd.append('foto_acta', this.fotos.foto_acta)
        if (this.photosToClear.foto_pizarra) fd.append('clear_foto_pizarra', '1')
        if (this.photosToClear.foto_acta) fd.append('clear_foto_acta', '1')

        const res = await this.$axios.post(
          `admin/mesas-segunda-vuelta/${this.resultadoMesa.id}/resultado?_method=PUT`,
          fd,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        this.patchRow(res?.data?.row)
        this.resultadoDialog = false
        this.$alert.success('Resultado de segunda vuelta guardado')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar el resultado')
      } finally {
        this.savingResult = false
      }
    },

    patchRow (row) {
      if (!row?.id) return
      this.rows = (this.rows || []).map(item => item.id === row.id ? row : item)
      this.recintoOptionsAll = this.buildRecintoOptions(this.rows)
      this.recintoOptions = this.recintoOptionsAll
    },

    patchRows (patchedRows) {
      const map = new Map((patchedRows || []).map(row => [row.id, row]))
      this.rows = (this.rows || []).map(item => map.get(item.id) || item)
      this.recintoOptionsAll = this.buildRecintoOptions(this.rows)
      this.recintoOptions = this.recintoOptionsAll
    }
  }
}
</script>

<style scoped>
.party-card {
  border-radius: 12px;
  padding: 12px;
  border: 1px solid #e2e2e2;
}

.photo-preview {
  width: 100%;
  max-height: 220px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #d7d7d7;
}
</style>
