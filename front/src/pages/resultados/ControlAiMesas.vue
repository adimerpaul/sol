<template>
  <q-page class="control-page q-pa-md">
    <div class="page-shell">
      <q-card flat class="panel-card q-mb-md">
        <q-card-section class="row items-center justify-between q-col-gutter-md">
          <div class="col-12 col-md">
            <div class="text-h5 text-weight-bold">Control IA de Mesas</div>
            <div class="text-caption text-grey-7 q-mt-xs">
              Flujo paralelo para lectura de papeletas con IA, revisión manual y confirmación.
            </div>
          </div>
          <div class="col-12 col-md-auto">
            <q-chip outline color="primary" icon="place">
              {{ filters.localidad_nombre }} / {{ filters.municipio_nombre }} / {{ filters.provincia_nombre }}
            </q-chip>
            <q-btn flat round dense icon="refresh" class="q-ml-sm" @click="loadBootstrap" :loading="loading" />
          </div>
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md">
        <div class="col-12 col-lg-6">
          <q-card flat class="panel-card full-height">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Entrada</div>
              <div class="text-caption text-grey-7">
                Selección de mesa y carga de la papeleta municipal para alcalde y concejal.
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-12 col-md-6">
                  <q-select
                    v-model="selectedRecintoId"
                    :options="filteredRecintoOptions"
                    emit-value
                    map-options
                    use-input
                    fill-input
                    hide-selected
                    input-debounce="0"
                    outlined
                    clearable
                    dense
                    label="Recinto"
                    @filter="filterRecintoOptions"
                    @update:model-value="onRecintoChange"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-select
                    v-model="selectedMesaId"
                    :options="mesaOptions"
                    option-label="label"
                    option-value="id"
                    emit-value
                    map-options
                    use-input
                    fill-input
                    hide-selected
                    input-debounce="250"
                    outlined
                    dense
                    label="Mesa"
                    @filter="filterMesaOptions"
                    @update:model-value="onMesaChange"
                  />
                </div>
              </div>

              <q-banner rounded class="context-banner q-mb-md" v-if="selectedMesa">
                <div class="text-weight-medium">
                  Mesa {{ selectedMesa.numero_mesa }} · {{ selectedMesa.recinto }}
                </div>
                <div class="text-caption">
                  {{ selectedMesa.localidad }} / {{ selectedMesa.municipio }} / {{ selectedMesa.provincia }}
                </div>
                <div class="text-caption">Habilitados: {{ selectedMesa.habilitados }}</div>
              </q-banner>

              <q-option-group
                v-model="form.fuente_tipo"
                :options="sourceTypeOptions"
                color="primary"
                inline
                class="q-mb-md"
              />

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <q-card flat bordered class="upload-card">
                    <q-card-section class="q-pa-sm">
                      <div class="row items-start justify-between q-mb-sm">
                        <div>
                          <div class="text-body2 text-weight-bold">Papeleta principal</div>
                          <div class="text-caption text-grey-7">
                            Alcalde y concejal
                          </div>
                        </div>
                        <q-icon name="upload_file" color="primary" size="26px" />
                      </div>

                      <q-select
                        v-if="form.fuente_tipo === 'resultado_slot'"
                        v-model="form.fuente_slot_departamental"
                        :options="officialSourceOptions"
                        emit-value
                        map-options
                        outlined
                        dense
                        label="Foto oficial"
                        :disable="!officialSourceOptions.length"
                      />

                      <q-file
                        v-else
                        v-model="form.foto_departamental"
                        outlined
                        dense
                        accept="image/*"
                        label="Subir imagen"
                      />

                      <q-img
                        v-if="departamentalPreview"
                        :src="departamentalPreview"
                        fit="contain"
                        class="upload-preview q-mt-sm"
                      />
                      <div class="row justify-end q-mt-sm" v-if="departamentalPreview">
                        <q-btn flat dense no-caps icon="zoom_in" label="Ver foto" @click="openImageDialog(departamentalPreview, 'Papeleta principal')" />
                      </div>
                    </q-card-section>
                  </q-card>
                </div>

                <div class="col-12 col-md-6">
                  <q-card flat bordered class="upload-card">
                    <q-card-section class="q-pa-sm">
                      <div class="row items-start justify-between q-mb-sm">
                        <div>
                          <div class="text-body2 text-weight-bold">Papeleta adicional</div>
                          <div class="text-caption text-grey-7">
                            Opcional si deseas comparar otra foto
                          </div>
                        </div>
                        <q-icon name="image_search" color="secondary" size="26px" />
                      </div>

                      <q-select
                        v-if="form.fuente_tipo === 'resultado_slot'"
                        v-model="form.fuente_slot_municipal"
                        :options="officialSourceOptions"
                        emit-value
                        map-options
                        outlined
                        dense
                        label="Foto oficial"
                        :disable="!officialSourceOptions.length"
                      />

                      <q-file
                        v-else
                        v-model="form.foto_municipal"
                        outlined
                        dense
                        accept="image/*"
                        label="Subir imagen"
                      />

                      <q-img
                        v-if="municipalPreview"
                        :src="municipalPreview"
                        fit="contain"
                        class="upload-preview q-mt-sm"
                      />
                      <div class="row justify-end q-mt-sm" v-if="municipalPreview">
                        <q-btn flat dense no-caps icon="zoom_in" label="Ver foto" @click="openImageDialog(municipalPreview, 'Papeleta adicional')" />
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <q-input
                v-model="form.observaciones"
                type="textarea"
                autogrow
                outlined
                dense
                label="Observaciones para el procesamiento"
                class="q-mt-md"
              />

              <div class="row justify-end q-mt-md">
                <q-btn
                  color="primary"
                  unelevated
                  no-caps
                  icon="auto_awesome"
                  label="Procesar con IA"
                  :loading="processing"
                  :disable="!canProcess"
                  @click="processImage"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-lg-6">
          <q-card flat class="panel-card full-height">
            <q-card-section class="row items-center justify-between">
              <div>
                <div class="text-subtitle1 text-weight-bold">Proceso</div>
                <div class="text-caption text-grey-7">Edición y confirmación del control leído por IA.</div>
              </div>
              <q-badge v-if="editableControl" :color="statusColor(editableControl.estado)">
                {{ editableControl.estado }}
              </q-badge>
            </q-card-section>
            <q-separator />
            <q-card-section v-if="editableControl">
              <div class="row q-col-gutter-sm q-mb-md">
                <div class="col-12 col-md-6" v-if="editableControl.imagen_url">
                  <q-card flat bordered class="preview-card">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7 q-mb-xs">Papeleta principal</div>
                      <q-img :src="editableControl.imagen_url" fit="contain" class="process-preview" />
                      <div class="row justify-end q-mt-sm">
                        <q-btn flat dense no-caps icon="zoom_in" label="Ampliar" @click="openImageDialog(editableControl.imagen_url, 'Papeleta principal')" />
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-12 col-md-6" v-if="editableControl.imagen_url_secundaria">
                  <q-card flat bordered class="preview-card">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7 q-mb-xs">Papeleta adicional</div>
                      <q-img :src="editableControl.imagen_url_secundaria" fit="contain" class="process-preview" />
                      <div class="row justify-end q-mt-sm">
                        <q-btn flat dense no-caps icon="zoom_in" label="Ampliar" @click="openImageDialog(editableControl.imagen_url_secundaria, 'Papeleta adicional')" />
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <div class="row q-col-gutter-sm q-mb-md">
                <div class="col-12 col-md-4">
                  <q-card flat bordered class="total-card">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7">Total Alcalde</div>
                      <div class="text-h6 text-weight-bold">{{ totalAlcalde }}</div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-12 col-md-4">
                  <q-card flat bordered class="total-card">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7">Total Concejal</div>
                      <div class="text-h6 text-weight-bold">{{ totalConcejal }}</div>
                    </q-card-section>
                  </q-card>
                </div>
                <div class="col-12 col-md-4">
                  <q-card flat bordered class="total-card total-card-accent">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7">Total General</div>
                      <div class="text-h6 text-weight-bold">{{ totalControlGeneral }}</div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <div class="row q-col-gutter-sm q-mb-md">
                <div class="col-12 col-md-4" v-for="category in categoryCards" :key="category.key">
                  <q-card flat bordered class="mini-category-card">
                    <q-card-section class="q-pa-sm">
                      <div class="text-caption text-grey-7">{{ category.label }}</div>
                      <q-input v-model.number="editableCategories[category.key].blancos" type="number" outlined dense label="Blancos" />
                      <q-input v-model.number="editableCategories[category.key].nulos" type="number" outlined dense label="Nulos" class="q-mt-xs" />
                      <q-input v-model.number="editableCategories[category.key].papeletas_no_utilizadas" type="number" outlined dense label="No utilizadas" class="q-mt-xs" />
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <q-input
                v-model="editableObservaciones"
                type="textarea"
                autogrow
                outlined
                dense
                label="Observaciones finales"
                class="q-mb-md"
              />

              <div class="table-shell">
                <q-markup-table flat bordered dense separator="cell" class="dense-control-table">
                  <thead>
                    <tr>
                      <th class="text-left">Partido</th>
                      <th class="text-right">Alcalde</th>
                      <th class="text-right">Concejal</th>
                      <th class="text-right">Conf.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in editableVotes" :key="row.partido_id">
                      <td class="text-left party-cell">
                        <div class="row items-center no-wrap q-gutter-sm">
                          <div class="party-avatar" :style="{ borderColor: row.color || '#c62828' }">
                            <q-img
                              v-if="row.icono_url || row.icono"
                              :src="partyIconUrl(row)"
                              fit="contain"
                              class="party-icon"
                            />
                            <span v-else class="party-fallback">{{ partyInitials(row) }}</span>
                          </div>
                          <div class="party-copy">
                            <div class="party-sigla">{{ row.sigla || row.nombre }}</div>
                            <div class="party-name">{{ row.nombre }}</div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <q-input v-model.number="row.votos_alcalde" type="number" dense outlined class="vote-input" input-class="text-right" />
                      </td>
                      <td>
                        <q-input v-model.number="row.votos_concejal" type="number" dense outlined class="vote-input" input-class="text-right" />
                      </td>
                      <td>
                        <q-input v-model.number="row.confianza" type="number" dense outlined class="vote-input vote-input-confidence" input-class="text-right" />
                      </td>
                    </tr>
                  </tbody>
                </q-markup-table>
              </div>

              <div class="row justify-end q-gutter-sm q-mt-md">
                <q-btn flat no-caps color="grey-7" label="Recargar lectura" @click="hydrateEditableControl(currentControl)" />
                <q-btn
                  color="positive"
                  unelevated
                  no-caps
                  icon="task_alt"
                  label="Confirmar control"
                  :loading="confirming"
                  @click="confirmControl"
                />
              </div>
            </q-card-section>

            <q-card-section v-else class="text-grey-7">
              Seleccione una mesa y cargue una o dos papeletas para iniciar el control.
            </q-card-section>
          </q-card>
        </div>
      </div>

      <div class="row q-col-gutter-md q-mt-md">
        <div class="col-12 col-xl-6">
          <q-card flat class="panel-card">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Gráficas</div>
              <div class="text-caption text-grey-7">Resumen por categoría según los controles ya procesados.</div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <q-tabs v-model="chartTab" dense align="left" active-color="primary" indicator-color="primary">
                <q-tab v-for="item in chartData" :key="item.key" :name="item.key" :label="item.label" />
              </q-tabs>
              <q-tab-panels v-model="chartTab" animated class="bg-transparent">
                <q-tab-panel v-for="item in chartData" :key="item.key" :name="item.key" class="q-px-none q-pb-none">
                  <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                      <apexchart type="pie" height="280" :options="pieOptions(item)" :series="item.series" />
                    </div>
                    <div class="col-12 col-md-6">
                      <apexchart type="bar" height="280" :options="barOptions(item)" :series="[{ name: item.label, data: item.series }]" />
                    </div>
                  </div>
                </q-tab-panel>
              </q-tab-panels>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-xl-6">
          <q-card flat class="panel-card">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold">Confirmaciones por usuario</div>
              <div class="text-caption text-grey-7">Ranking de usuarios por recintos distintos y confirmaciones realizadas.</div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <div v-if="userChartData.length" class="user-chart-wrap">
                <apexchart
                  type="bar"
                  height="320"
                  :options="userBarOptions"
                  :series="userBarSeries"
                />
              </div>
              <div v-else class="text-caption text-grey-7">
                AÃºn no hay confirmaciones para mostrar en este grÃ¡fico.
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <q-card flat class="panel-card q-mt-md">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-subtitle1 text-weight-bold">Mesas del control</div>
            <div class="text-caption text-grey-7">Tablero general de todas las mesas del alcance seleccionado.</div>
          </div>
          <q-badge color="primary">{{ filteredMesaBoard.length }} mesas</q-badge>
        </q-card-section>
        <q-separator />
        <q-card-section class="q-pb-none">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6 col-xl-4">
              <q-select
                v-model="mesaBoardRecintoFilter"
                :options="filteredMesaBoardRecintoOptions"
                emit-value
                map-options
                use-input
                fill-input
                hide-selected
                outlined
                dense
                clearable
                label="Filtrar por recinto"
                @filter="filterMesaBoardRecintos"
              />
            </div>
            <div class="col-12 col-md-6 col-xl-4">
              <q-select
                v-model="mesaBoardConfirmadorFilter"
                :options="filteredMesaBoardConfirmadorOptions"
                emit-value
                map-options
                use-input
                fill-input
                hide-selected
                outlined
                dense
                clearable
                label="Filtrar por confirmador"
                @filter="filterMesaBoardConfirmadores"
              />
            </div>
          </div>
        </q-card-section>
        <q-card-section class="mesa-board-grid">
          <q-card
            flat
            bordered
            v-for="mesa in filteredMesaBoard"
            :key="mesa.id"
            class="mesa-board-card"
            :class="`mesa-${mesa.color}`"
            @click="focusMesa(mesa.id)"
          >
            <q-card-section class="q-pa-sm">
              <div class="text-weight-bold">Mesa {{ mesa.numero_mesa }}</div>
              <div class="text-caption text-grey-7">{{ mesa.recinto }}</div>
              <q-badge :color="mesa.color" class="q-mt-sm">{{ mesa.status }}</q-badge>
              <div v-if="mesa.confirmado_por" class="mesa-confirmed-by q-mt-xs">
                Confirmado: {{ mesa.confirmado_por }}
              </div>
            </q-card-section>
          </q-card>
        </q-card-section>
      </q-card>

      <q-inner-loading :showing="loading">
        <q-spinner color="primary" size="42px" />
      </q-inner-loading>

      <q-dialog v-model="imageDialog.open" maximized>
        <q-card class="bg-black text-white">
          <q-toolbar>
            <q-toolbar-title>{{ imageDialog.title }}</q-toolbar-title>
            <q-btn flat round dense icon="remove" @click="zoomOutImage" />
            <q-btn flat round dense icon="add" @click="zoomInImage" />
            <q-btn flat round dense icon="restart_alt" @click="resetImageZoom" />
            <q-btn flat round dense icon="close" v-close-popup />
          </q-toolbar>
          <q-card-section class="dialog-image-wrap">
            <img
              v-if="imageDialog.src"
              :src="imageDialog.src"
              :style="{ transform: `scale(${imageDialog.zoom})` }"
              class="dialog-image"
            />
          </q-card-section>
        </q-card>
      </q-dialog>
    </div>
  </q-page>
</template>

<script>
const CATEGORY_CARDS = [
  { key: 'alcalde', label: 'Alcalde' },
  { key: 'concejal', label: 'Concejal' }
]

const EMPTY_CATEGORIES = () => ({
  concejal: { blancos: 0, nulos: 0, papeletas_no_utilizadas: 0 },
  alcalde: { blancos: 0, nulos: 0, papeletas_no_utilizadas: 0 }
})

export default {
  name: 'ControlAiMesas',
  data () {
    return {
      loading: false,
      processing: false,
      confirming: false,
      filters: {},
      recintos: [],
      mesaBoard: [],
      chartData: [],
      userChartData: [],
      mesaBoardRecintoFilter: null,
      mesaBoardConfirmadorFilter: null,
      filteredMesaBoardRecintoOptions: [],
      filteredMesaBoardConfirmadorOptions: [],
      selectedRecintoId: null,
      selectedMesaId: null,
      selectedMesa: null,
      filteredRecintoOptions: [],
      mesaOptions: [],
      currentControl: null,
      editableControl: null,
      editableCategories: EMPTY_CATEGORIES(),
      editableVotes: [],
      editableObservaciones: '',
      imageDialog: {
        open: false,
        src: null,
        title: '',
        zoom: 1
      },
      chartTab: 'alcalde',
      form: {
        fuente_tipo: 'upload',
        fuente_slot_departamental: null,
        fuente_slot_municipal: null,
        foto_departamental: null,
        foto_municipal: null,
        observaciones: ''
      }
    }
  },
  computed: {
    recintoOptions () {
      return (this.recintos || []).map(item => ({
        label: `${item.nombre} / ${this.filters.localidad_nombre || ''} / ${this.filters.municipio_nombre || ''} / ${this.filters.provincia_nombre || ''}`.replace(/ \/  \/ /g, ' / '),
        value: item.id
      }))
    },
    sourceTypeOptions () {
      return [
        { label: 'Subir papeletas', value: 'upload' },
        { label: 'Usar fotos oficiales', value: 'resultado_slot', disable: !this.officialSourceOptions.length }
      ]
    },
    officialSourceOptions () {
      return (this.selectedMesa?.fuentes_oficiales || []).map(item => ({
        label: item.label,
        value: item.slot
      }))
    },
    canProcess () {
      if (!this.selectedMesaId) return false
      if (this.form.fuente_tipo === 'upload') {
        return !!this.form.foto_departamental || !!this.form.foto_municipal
      }
      return !!this.form.fuente_slot_departamental || !!this.form.fuente_slot_municipal
    },
    totalAlcalde () {
      const votos = this.editableVotes.reduce((acc, row) => acc + Number(row.votos_alcalde || 0), 0)
      return votos + this.categoryTotal('alcalde')
    },
    totalConcejal () {
      const votos = this.editableVotes.reduce((acc, row) => acc + Number(row.votos_concejal || 0), 0)
      return votos + this.categoryTotal('concejal')
    },
    totalControlGeneral () {
      return this.totalAlcalde + this.totalConcejal
    },
    userBarSeries () {
      return [
        {
          name: 'Recintos',
          data: this.userChartData.map(item => Number(item.recintos || 0))
        },
        {
          name: 'Confirmaciones',
          data: this.userChartData.map(item => Number(item.confirmaciones || 0))
        }
      ]
    },
    userBarOptions () {
      return {
        chart: { toolbar: { show: false } },
        colors: ['#9f1239', '#d97706'],
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '58%'
          }
        },
        dataLabels: { enabled: true },
        xaxis: {
          categories: this.userChartData.map(item => item.usuario)
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left'
        },
        tooltip: {
          shared: true,
          intersect: false
        }
      }
    },
    mesaBoardRecintoOptions () {
      return [...new Set((this.mesaBoard || []).map(item => item.recinto).filter(Boolean))]
        .sort((a, b) => String(a).localeCompare(String(b)))
        .map(item => ({
          label: item,
          value: item
        }))
    },
    mesaBoardConfirmadorOptions () {
      return [...new Set((this.mesaBoard || []).map(item => item.confirmado_por).filter(Boolean))]
        .sort((a, b) => String(a).localeCompare(String(b)))
        .map(item => ({
          label: item,
          value: item
        }))
    },
    filteredMesaBoard () {
      return (this.mesaBoard || []).filter(mesa => {
        const recintoOk = !this.mesaBoardRecintoFilter || mesa.recinto === this.mesaBoardRecintoFilter
        const confirmadorOk = !this.mesaBoardConfirmadorFilter || mesa.confirmado_por === this.mesaBoardConfirmadorFilter
        return recintoOk && confirmadorOk
      })
    },
    categoryCards () {
      return CATEGORY_CARDS
    },
    departamentalPreview () {
      if (this.form.fuente_tipo === 'upload') {
        return this.buildLocalPreview(this.form.foto_departamental)
      }
      return this.officialPreview(this.form.fuente_slot_departamental)
    },
    municipalPreview () {
      if (this.form.fuente_tipo === 'upload') {
        return this.buildLocalPreview(this.form.foto_municipal)
      }
      return this.officialPreview(this.form.fuente_slot_municipal)
    }
  },
  async mounted () {
    await this.loadBootstrap()
    await this.loadMesaOptions('')
  },
  methods: {
    syncMesaBoardFilterOptions () {
      this.filteredMesaBoardRecintoOptions = this.mesaBoardRecintoOptions
      this.filteredMesaBoardConfirmadorOptions = this.mesaBoardConfirmadorOptions
    },
    syncFilteredRecintoOptions () {
      this.filteredRecintoOptions = this.recintoOptions
    },
    filterRecintoOptions (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.syncFilteredRecintoOptions()
          return
        }

        this.filteredRecintoOptions = this.recintoOptions.filter(option => option.label.toLowerCase().includes(needle))
      })
    },
    filterMesaBoardRecintos (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.filteredMesaBoardRecintoOptions = this.mesaBoardRecintoOptions
          return
        }

        this.filteredMesaBoardRecintoOptions = this.mesaBoardRecintoOptions.filter(option => option.label.toLowerCase().includes(needle))
      })
    },
    filterMesaBoardConfirmadores (val, update) {
      update(() => {
        const needle = String(val || '').trim().toLowerCase()
        if (!needle) {
          this.filteredMesaBoardConfirmadorOptions = this.mesaBoardConfirmadorOptions
          return
        }

        this.filteredMesaBoardConfirmadorOptions = this.mesaBoardConfirmadorOptions.filter(option => option.label.toLowerCase().includes(needle))
      })
    },
    categoryTotal (key) {
      const row = this.editableCategories?.[key] || {}
      return Number(row.blancos || 0) + Number(row.nulos || 0) + Number(row.papeletas_no_utilizadas || 0)
    },
    partyIconUrl (row) {
      if (row?.icono_url) {
        return row.icono_url
      }

      if (row?.icono) {
        return `${this.$url}/../images/partidos/${row.icono}`
      }

      return null
    },
    partyInitials (row) {
      const base = String(row?.sigla || row?.nombre || '?').trim()
      return base.slice(0, 2).toUpperCase()
    },
    statusColor (status) {
      if (status === 'confirmado' || status === 'completo') return 'positive'
      if (status === 'procesado' || status === 'parcial') return 'warning'
      return 'negative'
    },
    openImageDialog (src, title) {
      this.imageDialog = {
        open: true,
        src,
        title,
        zoom: 1
      }
    },
    zoomInImage () {
      this.imageDialog.zoom = Math.min(this.imageDialog.zoom + 0.25, 4)
    },
    zoomOutImage () {
      this.imageDialog.zoom = Math.max(this.imageDialog.zoom - 0.25, 0.5)
    },
    resetImageZoom () {
      this.imageDialog.zoom = 1
    },
    pieOptions (item) {
      return {
        labels: item.labels,
        colors: item.colors,
        legend: { position: 'bottom' },
        dataLabels: {
          enabled: true,
          formatter: (val) => `${Number(val || 0).toFixed(1)}%`
        }
      }
    },
    barOptions (item) {
      return {
        chart: { toolbar: { show: false } },
        colors: item.colors,
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 6,
            distributed: true
          }
        },
        xaxis: { categories: item.labels },
        dataLabels: { enabled: true },
        legend: { show: false }
      }
    },
    buildLocalPreview (file) {
      return file ? URL.createObjectURL(file) : null
    },
    officialPreview (slot) {
      const match = (this.selectedMesa?.fuentes_oficiales || []).find(item => item.slot === slot)
      return match?.url || null
    },
    async loadBootstrap (extraParams = {}) {
      this.loading = true
      try {
        const params = {
          recinto_id: this.selectedRecintoId || undefined,
          mesa_id: this.selectedMesaId || undefined,
          ...extraParams
        }
        const { data } = await this.$axios.get('admin/ia-control/bootstrap', { params })
        this.filters = data.filters || {}
        this.recintos = Array.isArray(data.recintos) ? data.recintos : []
        this.syncFilteredRecintoOptions()
        this.mesaBoard = Array.isArray(data.mesa_board) ? data.mesa_board : []
        this.chartData = Array.isArray(data.chart_data) ? data.chart_data : []
        this.userChartData = Array.isArray(data.user_chart_data) ? data.user_chart_data : []
        this.syncMesaBoardFilterOptions()
        this.selectedMesa = data.selected_mesa || null

        if (!this.selectedRecintoId && this.recintos.length) {
          this.selectedRecintoId = this.recintos[0].id
        }

        if (this.selectedMesa?.id) {
          this.selectedMesaId = this.selectedMesa.id
        }

        if (this.selectedMesa?.latest_control) {
          this.currentControl = data.selected_mesa.latest_control
          this.hydrateEditableControl(this.currentControl)
        } else if (!this.selectedMesaId) {
          this.currentControl = null
          this.editableControl = null
          this.editableCategories = EMPTY_CATEGORIES()
          this.editableVotes = []
          this.editableObservaciones = ''
        }

        if (this.chartData.length && !this.chartData.find(item => item.key === this.chartTab)) {
          this.chartTab = this.chartData[0].key
        }
      } catch (error) {
        this.$q.notify({
          type: 'negative',
          message: error?.response?.data?.message || 'No se pudo cargar el módulo de Control IA.'
        })
      } finally {
        this.loading = false
      }
    },
    async loadMesaOptions (query = '') {
      try {
        const { data } = await this.$axios.get('admin/ia-control/mesas-options', {
          params: {
            q: query || undefined,
            recinto_id: this.selectedRecintoId || undefined
          }
        })
        this.mesaOptions = Array.isArray(data) ? data : []
      } catch (error) {
        this.mesaOptions = []
      }
    },
    async filterMesaOptions (val, update) {
      update(async () => {
        await this.loadMesaOptions(val)
      })
    },
    resetSourceForm () {
      this.form.fuente_slot_departamental = null
      this.form.fuente_slot_municipal = null
      this.form.foto_departamental = null
      this.form.foto_municipal = null
    },
    async onRecintoChange () {
      this.selectedMesaId = null
      this.selectedMesa = null
      this.currentControl = null
      this.editableControl = null
      this.resetSourceForm()
      await this.loadBootstrap({ recinto_id: this.selectedRecintoId, mesa_id: undefined })
      await this.loadMesaOptions('')
    },
    async onMesaChange () {
      this.resetSourceForm()
      await this.loadBootstrap({ mesa_id: this.selectedMesaId, recinto_id: this.selectedRecintoId })
    },
    focusMesa (mesaId) {
      this.selectedMesaId = mesaId
      this.onMesaChange()
    },
    hydrateEditableControl (control) {
      if (!control) return
      this.editableControl = { ...control }
      this.editableCategories = JSON.parse(JSON.stringify(control.categorias || EMPTY_CATEGORIES()))
      this.editableVotes = (control.votos || []).map(row => ({
        ...row,
        votos_concejal: Number(row.votos_concejal || 0),
        votos_alcalde: Number(row.votos_alcalde || 0),
        confianza: row.confianza === null || row.confianza === undefined ? null : Number(row.confianza)
      }))
      this.editableObservaciones = control.observaciones || ''
    },
    async processImage () {
      this.processing = true
      try {
        const formData = new FormData()
        formData.append('mesa_id', this.selectedMesaId)
        formData.append('fuente_tipo', this.form.fuente_tipo)
        if (this.form.fuente_slot_departamental) formData.append('fuente_slot_departamental', this.form.fuente_slot_departamental)
        if (this.form.fuente_slot_municipal) formData.append('fuente_slot_municipal', this.form.fuente_slot_municipal)
        if (this.form.observaciones) formData.append('observaciones', this.form.observaciones)
        if (this.form.foto_departamental) formData.append('foto_departamental', this.form.foto_departamental)
        if (this.form.foto_municipal) formData.append('foto_municipal', this.form.foto_municipal)

        const { data } = await this.$axios.post('admin/ia-control/process', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        this.currentControl = data.control
        this.hydrateEditableControl(data.control)
        this.$q.notify({ type: 'positive', message: data.message || 'Papeletas procesadas.' })
        await this.loadBootstrap({ mesa_id: this.selectedMesaId, recinto_id: this.selectedRecintoId })
      } catch (error) {
        this.$q.notify({
          type: 'negative',
          message: error?.response?.data?.message || 'No se pudo procesar la imagen con IA.'
        })
      } finally {
        this.processing = false
      }
    },
    async confirmControl () {
      if (!this.editableControl?.id) return

      this.confirming = true
      try {
        const payload = {
          observaciones: this.editableObservaciones,
          categorias: this.editableCategories,
          votos: this.editableVotes.map(row => ({
            partido_id: row.partido_id,
            votos_concejal: Number(row.votos_concejal || 0),
            votos_alcalde: Number(row.votos_alcalde || 0),
            confianza: row.confianza === null || row.confianza === '' ? null : Number(row.confianza)
          }))
        }

        const { data } = await this.$axios.post(`admin/ia-control/${this.editableControl.id}/confirm`, payload)
        this.currentControl = data.control
        this.hydrateEditableControl(data.control)
        this.$q.notify({ type: 'positive', message: data.message || 'Control confirmado.' })
        await this.loadBootstrap({ mesa_id: this.selectedMesaId, recinto_id: this.selectedRecintoId })
      } catch (error) {
        this.$q.notify({
          type: 'negative',
          message: error?.response?.data?.message || 'No se pudo confirmar el control.'
        })
      } finally {
        this.confirming = false
      }
    }
  }
}
</script>

<style scoped>
.control-page {
  background:
    radial-gradient(circle at top left, rgba(183, 28, 28, 0.12), transparent 30%),
    linear-gradient(180deg, #fff8f8 0%, #f6f7fb 100%);
}

.page-shell {
  position: relative;
}

.panel-card,
.upload-card,
.preview-card,
.mini-category-card,
.status-card,
.mesa-board-card {
  background: white;
}

.panel-card {
  border-radius: 22px;
  border: 1px solid #ebeef5;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
}

.context-banner {
  background: linear-gradient(135deg, #fff4f4 0%, #fff 100%);
  border: 1px solid #ffd8d8;
}

.upload-card,
.preview-card {
  border-radius: 18px;
  border-color: #e8ebf2;
}

.total-card {
  border-radius: 16px;
  border-color: #e8ebf2;
}

.total-card-accent {
  background: linear-gradient(135deg, #fff4f4 0%, #ffffff 100%);
}

.upload-preview,
.process-preview {
  height: 190px;
  background: #fff7f7;
  border-radius: 12px;
}

.mini-category-card {
  border-radius: 16px;
}

.party-color {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}

.table-shell {
  max-height: 340px;
  overflow: auto;
}

.dense-control-table {
  font-size: 12px;
}

.dense-control-table thead th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #fff8f8;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.dense-control-table th,
.dense-control-table td {
  padding: 6px 8px;
  vertical-align: middle;
}

.party-cell {
  min-width: 240px;
}

.party-avatar {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  border: 1px solid #eadede;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  overflow: hidden;
}

.party-icon {
  width: 100%;
  height: 100%;
}

.party-fallback {
  font-size: 10px;
  font-weight: 700;
  color: #7a2e2e;
}

.party-copy {
  min-width: 0;
}

.party-sigla {
  font-size: 12px;
  font-weight: 700;
  line-height: 1.15;
}

.party-name {
  font-size: 11px;
  color: #6b7280;
  line-height: 1.15;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

.vote-input {
  min-width: 92px;
}

.vote-input :deep(.q-field__control) {
  height: 34px;
  border-radius: 10px;
  background: #fffdfd;
}

.vote-input :deep(.q-field__native),
.vote-input :deep(.q-field__input) {
  font-size: 13px;
  font-weight: 600;
  padding-right: 4px;
}

.vote-input-confidence {
  min-width: 82px;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
}

.mesa-board-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
}

.mesa-board-card {
  border-radius: 16px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.mesa-board-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.1);
}

.mesa-positive {
  border-color: rgba(46, 125, 50, 0.28);
  background: #f1fff3;
}

.mesa-warning {
  border-color: rgba(249, 168, 37, 0.28);
  background: #fff9eb;
}

.mesa-negative {
  border-color: rgba(198, 40, 40, 0.2);
  background: #fff2f2;
}

.mesa-confirmed-by {
  font-size: 11px;
  line-height: 1.25;
  color: #5b6472;
}

.user-chart-wrap {
  max-width: 900px;
  margin: 0 auto;
}

.dialog-image-wrap {
  height: calc(100vh - 80px);
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dialog-image {
  max-width: 100%;
  max-height: 100%;
  transform-origin: center center;
  transition: transform 0.15s ease;
}
</style>
