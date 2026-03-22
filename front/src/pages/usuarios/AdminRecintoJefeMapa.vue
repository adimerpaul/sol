<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section>
        <div class="row items-center q-col-gutter-sm">
          <div class="col">
            <div class="text-h6 text-weight-bold">
              Asignacion de Jefes por Recinto (Mapa)
              <q-btn icon="refresh" round dense flat class="q-ml-sm" @click="load" />
            </div>
            <div class="text-caption text-grey-7">Selecciona un recinto en el mapa y asigna sus jefes</div>
          </div>
          <div class="col-auto">
            <div class="row items-center no-wrap q-gutter-sm">
              <q-btn-dropdown
                color="primary"
                icon="print"
                label="Impresiones"
                no-caps
                :disable="loading"
                :loading="printing"
              >
                <q-list>
                  <q-item clickable v-close-popup @click="openPrint('mesas-sin-delegado')">
                    <q-item-section avatar><q-icon name="groups" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Mesas sin delegado</q-item-label>
                      <q-item-label caption>Incluye jefe de recinto y celulares</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openPrint('recintos-sin-jefe')">
                    <q-item-section avatar><q-icon name="domain_disabled" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Recintos sin jefe</q-item-label>
                      <q-item-label caption>Resumen por recinto y mesas faltantes</q-item-label>
                    </q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openPrint('jefes-mesas-delegados')">
                    <q-item-section avatar><q-icon name="badge" /></q-item-section>
                    <q-item-section>
                      <q-item-label>Jefes, mesas y delegados</q-item-label>
                      <q-item-label caption>Formato agrupado por jefe de recinto</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>

              <q-btn
                color="positive"
                icon="person_add"
                label="Agregar delegado"
                no-caps
                :disable="loading"
                @click="openDelegadoDialog"
              />
            </div>
          </div>
        </div>

        <div class="row items-center q-col-gutter-sm q-mt-sm">
          <div class="col-auto">
            <q-chip color="primary" text-color="white" outline>
              Total de recintos: {{ recintos.length }}
            </q-chip>
          </div>

          <div class="col-auto">
            <q-chip color="primary" text-color="white" outline>
              Total de jefes: {{ jefes.length }}
            </q-chip>
          </div>

          <div class="col-auto">
            <q-chip color="negative" text-color="white" outline>
              Recintos sin jefe: {{ recintos.filter(r => !r.jefe?.length).length }}
            </q-chip>
          </div>
          <div class="col-auto">
            <q-chip color="orange" text-color="white" outline>
              Recintos con mesas faltantes: {{ recintos.filter(r => r.mesas_faltan > 0).length }}
            </q-chip>
          </div>
        </div>

        <div class="row q-col-gutter-sm q-mt-sm">
          <div class="col-12 col-sm-4 col-md-3">
            <q-select
              v-model="filters.provincia_id"
              :options="provinciasOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              clearable
              label="Provincia"
              @update:model-value="onProvinciaChange"
            />
          </div>
          <div class="col-12 col-sm-4 col-md-3">
            <q-select
              v-model="filters.municipio_id"
              :options="municipiosOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              clearable
              label="Municipio"
              :disable="!filters.provincia_id"
              @update:model-value="onMunicipioChange"
            />
          </div>
          <div class="col-12 col-sm-4 col-md-3">
            <q-select
              v-model="filters.localidad_id"
              :options="localidadesOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              clearable
              label="Localidad"
              :disable="!filters.municipio_id"
              @update:model-value="load"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-btn
              flat
              color="grey-8"
              icon="filter_alt_off"
              label="Limpiar filtros"
              no-caps
              class="full-width"
              @click="clearGeoFilters"
            />
          </div>

          <div class="col-12 col-md-6">
            <q-select
              v-model="recintoPick"
              :options="recintosOptions"
              use-input
              fill-input
              hide-selected
              input-debounce="250"
              dense
              outlined
              label="Buscar recinto rapido..."
              option-label="label"
              option-value="value"
              emit-value
              map-options
              clearable
              @filter="filterRecintos"
              @update:model-value="onPickRecinto"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>

              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      {{ scope.opt.nombre }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.jefeNombre }}
                    </q-item-label>
                    <q-item-label caption>
                      Mesas: {{ scope.opt.mesas_total }} · Asignadas: {{ scope.opt.mesas_asignadas }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge outline :color="scope.opt.okDelegados ? 'positive' : 'negative'">
                      {{ scope.opt.okDelegados ? 'completo' : 'falta' }}
                    </q-badge>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-8">
            <RecintosMapaMarkers
              ref="mapMarkersRef"
              :markers="recintos"
              :focus-recinto="focusRecinto"
              @select="onSelectRecinto"
            />
          </div>

          <div class="col-12 col-md-4">
            <div v-if="!selected">
              <q-banner dense class="bg-grey-2">Selecciona un recinto en el mapa</q-banner>
            </div>

            <div v-else>
              <div class="text-subtitle1 text-weight-bold">{{ selected.nombre }}</div>

              <q-badge class="q-mt-xs" outline :color="selected?.jefe?.length ? 'positive' : 'negative'">
                {{ selected?.jefe?.length ? `${selected.jefe.length} jefe(s) asignado(s)` : 'Sin jefe asignado' }}
              </q-badge>
              <div class="row q-gutter-xs q-mt-xs">
                <q-badge outline color="grey-8">Mesas: {{ selected.mesas_total }}</q-badge>
                <q-badge outline color="primary">Asignadas: {{ selected.mesas_asignadas }}</q-badge>
                <q-badge outline :color="selected.mesas_faltan > 0 ? 'negative' : 'positive'">
                  {{ selected.mesas_faltan > 0 ? `Faltan ${selected.mesas_faltan}` : 'Delegados OK' }}
                </q-badge>
              </div>

              <q-card flat bordered class="q-mt-md bg-grey-1">
                <q-card-section class="q-pb-sm">
                  <div class="text-subtitle2 text-weight-medium">Jefes asignados</div>
                </q-card-section>

                <q-card-section class="q-pt-none">
                  <q-markup-table dense flat bordered separator="horizontal">
                    <thead>
                    <tr>
                      <th class="text-left">Jefe</th>
                      <th class="text-left">Celular</th>
                      <th class="text-center" style="width: 110px;">Super jefe</th>
                      <th class="text-right" style="width: 90px;">Quitar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="jefe in (selected.jefe || [])" :key="jefe.id">
                      <td>
                        <div class="text-weight-medium">{{ jefe.name }}</div>
                        <div class="text-caption text-grey-7">{{ jefe.username }}</div>
                      </td>
                      <td>{{ jefe.celular || 'Sin celular' }}</td>
                      <td class="text-center">
                        <q-checkbox
                          :model-value="!!jefe.super_jefe"
                          dense
                          :disable="savingJefe"
                          @update:model-value="toggleSuperJefe(jefe, $event)"
                        />
                      </td>
                      <td class="text-right">
                        <q-btn
                          flat
                          dense
                          color="negative"
                          icon="delete"
                          label="Quitar"
                          no-caps
                          :loading="savingJefe && pendingJefeActionId === jefe.id"
                          @click="confirmRemoveJefe(jefe)"
                        />
                      </td>
                    </tr>
                    <tr v-if="!(selected.jefe || []).length">
                      <td colspan="4" class="text-center text-grey-7 q-pa-md">
                        Sin jefes asignados
                      </td>
                    </tr>
                    </tbody>
                  </q-markup-table>
                </q-card-section>
              </q-card>

              <div class="row q-col-gutter-sm q-mt-md">
                <div class="col-12 col-sm-8">
                  <q-select
                    v-model="jefeIdToAdd"
                    :options="jefesOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    use-input
                    input-debounce="0"
                    clearable
                    dense
                    outlined
                    label="Jefe de Recinto o Delegado"
                    @filter="filterJefes"
                  />
                </div>
                <div class="col-12 col-sm-4">
                  <q-btn
                    color="primary"
                    icon="add"
                    label="Agregar"
                    class="full-width"
                    no-caps
                    :disable="!jefeIdToAdd"
                    :loading="savingJefe && pendingJefeActionId === jefeIdToAdd"
                    @click="addJefe"
                  />
                </div>
              </div>

              <q-separator class="q-my-md" />

              <div class="row items-center q-mb-sm">
                <div class="text-subtitle2 text-weight-medium">Mesas</div>
                <q-space />
                <q-btn icon="refresh" round dense flat :loading="loadingMesas" @click="loadMesas" />
              </div>

              <q-banner v-if="loadingMesas" dense class="bg-grey-2 q-mb-sm">
                Cargando mesas...
              </q-banner>

              <q-banner v-else-if="mesas.length === 0" dense class="bg-grey-2 q-mb-sm">
                No hay mesas para este recinto.
              </q-banner>

              <q-list v-else bordered separator class="rounded-borders">
                <q-item v-for="mesa in mesas" :key="mesa.id">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      Mesa {{ mesa.numero_mesa }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ mesa.delegado ? `${mesa.delegado.name} (${mesa.delegado.username})` : 'Sin delegado asignado' }}
                    </q-item-label>
                    <q-item-label caption>
                      Estado: {{ mesa.estado || (mesa.delegado_id ? 'ASIGNADA' : 'PENDIENTE') }}
                    </q-item-label>

                    <div class="row q-col-gutter-sm q-mt-sm">
                      <div class="col-12">
                        <q-select
                          v-model="mesa.delegadoDraftId"
                          :options="delegadosOptions"
                          option-label="label"
                          option-value="value"
                          emit-value
                          map-options
                          use-input
                          input-debounce="0"
                          clearable
                          dense
                          outlined
                          label="Delegado de Mesa"
                          @filter="filterDelegados"
                        />
                      </div>
                      <div class="col-6">
                        <q-btn
                          color="primary"
                          label="Guardar delegado"
                          no-caps
                          class="full-width"
                          :loading="savingMesaId === mesa.id"
                          @click="saveMesa(mesa)"
                        />
                      </div>
                      <div class="col-6">
                        <q-btn
                          flat
                          color="negative"
                          label="Quitar"
                          no-caps
                          class="full-width"
                          :disable="!mesa.delegado_id && !mesa.delegadoDraftId"
                          @click="clearMesa(mesa)"
                        />
                      </div>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="delegadoDialog" persistent>
      <q-card style="width: 760px; max-width: 96vw">
        <q-card-section class="q-pb-none row items-center">
          <div class="text-weight-bold">Alta rapida de delegado de mesa</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="closeDelegadoDialog" />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit="createDelegadoRapido">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.nombres"
                  label="Nombre(s) *"
                  dense
                  outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.apellido_paterno"
                  label="Apellido paterno"
                  dense
                  outlined
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.apellido_materno"
                  label="Apellido materno *"
                  dense
                  outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.ci"
                  label="CI *"
                  dense
                  outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.fecha_nacimiento"
                  type="date"
                  label="Fecha de nacimiento *"
                  dense
                  outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.celular"
                  label="Celular"
                  dense
                  outlined
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.bloque"
                  label="Bloque / agrupacion / organizacion *"
                  dense
                  outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="delegadoForm.numero_mesa"
                  label="Numero de mesa"
                  dense
                  outlined
                />
              </div>
              <div class="col-12 col-md-4">
                <q-select
                  v-model="delegadoForm.recinto_id"
                  :options="recintosDialogOptions"
                  option-label="label"
                  option-value="value"
                  emit-value
                  map-options
                  use-input
                  input-debounce="0"
                  clearable
                  dense
                  outlined
                  label="Recinto"
                  @filter="filterRecintosDialog"
                />
              </div>
            </div>

            <div class="text-caption text-grey-7 q-mt-sm">
              El rol se guardara como Delegado de Mesa y el codigo de ingreso se generara automaticamente si no existe uno.
            </div>

            <div class="text-right q-mt-md">
              <q-btn color="negative" label="Cancelar" no-caps :disable="savingDelegado" @click="closeDelegadoDialog" />
              <q-btn color="primary" label="Guardar delegado" type="submit" no-caps :loading="savingDelegado" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import RecintosMapaMarkers from 'components/RecintosMapaMarkers.vue'

export default {
  name: 'AdminRecintoJefeMapa',
  components: { RecintosMapaMarkers },

  data () {
    return {
      recintos: [],
      jefes: [],
      jefesOptions: [],
      jefesOptionsAll: [],
      delegadosOptions: [],
      delegadosOptionsAll: [],
      geo: {
        provincias: [],
        municipios: [],
        localidades: [],
        defaults: {
          provincia_id: 57,
          municipio_id: 191,
          localidad_id: 1988
        }
      },
      filters: {
        provincia_id: 57,
        municipio_id: 191,
        localidad_id: 1988
      },
      selected: null,
      jefeIdToAdd: null,
      savingJefe: false,
      pendingJefeActionId: null,
      mesas: [],
      loadingMesas: false,
      savingMesaId: null,
      recintoPick: null,
      recintosOptions: [],
      focusRecinto: null,
      loading: false,
      printing: false,
      delegadoDialog: false,
      savingDelegado: false,
      delegadoForm: this.emptyDelegadoForm(),
      recintosDialogOptions: []
    }
  },

  mounted () {
    this.load()
  },

  computed: {
    provinciasOptions () {
      return (this.geo.provincias || []).map(item => ({
        label: item.nombre,
        value: item.id
      }))
    },

    municipiosOptions () {
      return (this.geo.municipios || [])
        .filter(item => !this.filters.provincia_id || item.provincia_id === this.filters.provincia_id)
        .map(item => ({
          label: item.nombre,
          value: item.id
        }))
    },

    localidadesOptions () {
      return (this.geo.localidades || [])
        .filter(item => !this.filters.municipio_id || item.municipio_id === this.filters.municipio_id)
        .map(item => ({
          label: item.nombre,
          value: item.id
        }))
    },

    recintosSelectableOptions () {
      return (this.recintos || []).map(x => ({
        value: x.id,
        label: `${x.nombre} - ${x.localidad_nombre || x.municipio_nombre || 'Sin ubicacion'}`
      }))
    }
  },

  methods: {
    emptyDelegadoForm () {
      return {
        nombres: '',
        apellido_paterno: '',
        apellido_materno: '',
        ci: '',
        fecha_nacimiento: '',
        bloque: '',
        celular: '',
        numero_mesa: '',
        recinto_id: this.selected?.id || null
      }
    },

    buildLoadParams () {
      return {
        provincia_id: this.filters.provincia_id || undefined,
        municipio_id: this.filters.municipio_id || undefined,
        localidad_id: this.filters.localidad_id || undefined
      }
    },

    async load () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('admin/mapa-recintos/bootstrap', {
          params: this.buildLoadParams()
        })
        if (data?.geo) {
          this.geo = {
            provincias: Array.isArray(data.geo.provincias) ? data.geo.provincias : [],
            municipios: Array.isArray(data.geo.municipios) ? data.geo.municipios : [],
            localidades: Array.isArray(data.geo.localidades) ? data.geo.localidades : [],
            defaults: {
              provincia_id: data.geo.defaults?.provincia_id ?? 57,
              municipio_id: data.geo.defaults?.municipio_id ?? 191,
              localidad_id: data.geo.defaults?.localidad_id ?? 1988
            }
          }
        }
        this.recintos = (Array.isArray(data?.recintos) ? data.recintos : []).map(this.normalizeRecinto)
        this.jefes = Array.isArray(data?.jefes) ? data.jefes : []
        this.jefesOptionsAll = this.buildJefesOptions(this.jefes)
        this.jefesOptions = this.jefesOptionsAll
        this.delegadosOptionsAll = (Array.isArray(data?.delegados) ? data.delegados : []).map(item => ({
          value: item.id,
          label: `${item.name} (${item.username})`
        }))
        this.delegadosOptions = this.delegadosOptionsAll
        this.recintosOptions = this.buildOptions(this.recintos)
        this.recintosDialogOptions = this.recintosSelectableOptions

        if (this.selected?.id) {
          const again = (this.recintos || []).find(x => x.id === this.selected.id)
          if (again) {
            this.applySelectedRecinto(again)
          } else {
            this.selected = null
            this.mesas = []
            this.recintoPick = null
            this.focusRecinto = null
          }
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar el mapa de recintos')
      } finally {
        this.loading = false
      }
    },

    normalizeRecinto (recinto) {
      return {
        ...recinto,
        jefe: Array.isArray(recinto?.jefe)
          ? recinto.jefe.map(jefe => ({
            ...jefe,
            super_jefe: !!jefe.super_jefe
          }))
          : []
      }
    },

    buildJefesOptions (list) {
      return (list || []).map(j => ({
        value: j.id,
        label: `${j.name || '-'} · ${j.role || 'Sin rol'} · ${j.celular || 'Sin celular'}`,
        search: `${j.name || ''} ${j.username || ''} ${j.celular || ''} ${j.role || ''}`.toLowerCase()
      }))
    },

    buildOptions (list) {
      return (list || []).map(x => {
        const jefes = Array.isArray(x?.jefe) ? x.jefe : []
        const jefeNombre = jefes.length
          ? jefes.map(j => `${j.name} (${j.celular || j.username || 'sin dato'})`).join(', ')
          : 'Sin jefe asignado'
        const tieneJefe = jefes.length > 0
        const mesasTotal = Number(x?.mesas_total || 0)
        const mesasAsignadas = Number(x?.mesas_asignadas || 0)
        const okDelegados = tieneJefe && (mesasTotal === 0 || mesasAsignadas >= mesasTotal)

        return {
          label: `${x.nombre} - ${x.localidad_nombre || x.municipio_nombre || ''} - ${jefeNombre}`,
          value: x.id,
          nombre: x.nombre,
          provinciaNombre: x.provincia_nombre || '',
          municipioNombre: x.municipio_nombre || '',
          localidadNombre: x.localidad_nombre || '',
          jefeNombre,
          tieneJefe,
          mesas_total: mesasTotal,
          mesas_asignadas: mesasAsignadas,
          okDelegados
        }
      })
    },

    buildMesasFromRecinto (recinto) {
      return (Array.isArray(recinto?.mesas) ? recinto.mesas : []).map(m => ({
        ...m,
        delegadoDraftId: m.delegado_id ?? null
      }))
    },

    applySelectedRecinto (recinto) {
      this.selected = this.normalizeRecinto(recinto)
      this.jefeIdToAdd = null
      this.jefesOptions = this.availableJefesOptions()
      this.recintoPick = recinto.id
      this.mesas = this.buildMesasFromRecinto(this.selected)
      this.delegadosOptions = this.delegadosOptionsAll
      if (this.delegadoDialog) {
        this.delegadoForm.recinto_id = recinto.id
      }
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        const base = this.buildOptions(this.recintos)

        if (!needle) {
          this.recintosOptions = base
          return
        }

        this.recintosOptions = base.filter(o =>
          o.nombre.toLowerCase().includes(needle) ||
          o.provinciaNombre.toLowerCase().includes(needle) ||
          o.municipioNombre.toLowerCase().includes(needle) ||
          o.localidadNombre.toLowerCase().includes(needle) ||
          o.jefeNombre.toLowerCase().includes(needle)
        )
      })
    },

    onProvinciaChange () {
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.load()
    },

    onMunicipioChange () {
      this.filters.localidad_id = null
      this.load()
    },

    clearGeoFilters () {
      this.filters.provincia_id = null
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.load()
    },

    filterJefes (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.jefesOptions = this.availableJefesOptions()
          return
        }

        this.jefesOptions = this.availableJefesOptions().filter(j =>
          String(j.search || '').includes(needle)
        )
      })
    },

    availableJefesOptions () {
      const assignedIds = new Set((this.selected?.jefe || []).map(j => j.id))
      return (this.jefesOptionsAll || []).filter(j => !assignedIds.has(j.value))
    },

    filterDelegados (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.delegadosOptions = this.delegadosOptionsAll
          return
        }

        this.delegadosOptions = (this.delegadosOptionsAll || []).filter(d =>
          String(d.label || '').toLowerCase().includes(needle)
        )
      })
    },

    openDelegadoDialog () {
      this.delegadoForm = this.emptyDelegadoForm()
      this.recintosDialogOptions = this.recintosSelectableOptions
      this.delegadoDialog = true
    },

    closeDelegadoDialog () {
      if (this.savingDelegado) return
      this.delegadoDialog = false
      this.delegadoForm = this.emptyDelegadoForm()
      this.recintosDialogOptions = this.recintosSelectableOptions
    },

    filterRecintosDialog (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        const base = this.recintosSelectableOptions

        if (!needle) {
          this.recintosDialogOptions = base
          return
        }

        this.recintosDialogOptions = base.filter(item =>
          String(item.label || '').toLowerCase().includes(needle)
        )
      })
    },

    async onPickRecinto (id) {
      if (!id) {
        this.focusRecinto = null
        return
      }

      const r = (this.recintos || []).find(x => x.id === id)
      if (!r) return

      await this.onSelectRecinto(r)

      this.focusRecinto = {
        id: r.id,
        latitud: r.latitud,
        longitud: r.longitud
      }
    },

    async onSelectRecinto (recinto) {
      this.applySelectedRecinto(recinto)
    },

    currentJefesPayload (overrideJefes = null) {
      const list = Array.isArray(overrideJefes) ? overrideJefes : (this.selected?.jefe || [])

      return list.map(jefe => ({
        id: jefe.id,
        super_jefe: !!jefe.super_jefe
      }))
    },

    async persistJefes (jefes, successMessage) {
      this.savingJefe = true
      try {
        await this.$axios.put(
          `admin/mapa-recintos/recintos/${this.selected.id}/jefe`,
          { jefes }
        )
        this.$alert.success(successMessage)
        await this.load()
        return true
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar jefes')
        return false
      } finally {
        this.savingJefe = false
        this.pendingJefeActionId = null
      }
    },

    async addJefe () {
      if (!this.jefeIdToAdd || !this.selected?.id) return
      const actuales = this.currentJefesPayload()
      const existe = actuales.some(j => j.id === this.jefeIdToAdd)
      const jefes = existe
        ? actuales
        : [...actuales, { id: this.jefeIdToAdd, super_jefe: false }]
      this.pendingJefeActionId = this.jefeIdToAdd
      this.jefeIdToAdd = null
      await this.persistJefes(jefes, 'Jefe agregado')
    },

    confirmRemoveJefe (jefe) {
      this.$q.dialog({
        title: 'Confirmar',
        message: `Quitar a ${jefe.name} de este recinto?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.removeJefe(jefe)
      })
    },

    async removeJefe (jefe) {
      if (!this.selected?.id) return
      this.pendingJefeActionId = jefe.id
      const jefes = this.currentJefesPayload(this.selected?.jefe || []).filter(item => item.id !== jefe.id)
      await this.persistJefes(jefes, 'Jefe quitado')
    },

    async toggleSuperJefe (jefe, value) {
      if (!this.selected?.id) return

      const previous = !!jefe.super_jefe
      jefe.super_jefe = !!value
      this.pendingJefeActionId = jefe.id

      const ok = await this.persistJefes(this.currentJefesPayload(), 'Super jefe actualizado')
      if (!ok) {
        jefe.super_jefe = previous
      }
    },

    async loadMesas () {
      if (!this.selected?.id) {
        this.mesas = []
        return
      }

      this.loadingMesas = true
      try {
        const recinto = (this.recintos || []).find(r => r.id === this.selected.id)
        this.mesas = this.buildMesasFromRecinto(recinto || this.selected)
        this.delegadosOptions = this.delegadosOptionsAll
      } finally {
        this.loadingMesas = false
      }
    },

    async saveMesa (mesa) {
      this.savingMesaId = mesa.id
      try {
        await this.$axios.put(`admin/mesas/${mesa.id}/delegado`, {
          delegado_id: mesa.delegadoDraftId || null
        })
        this.$alert.success(mesa.delegadoDraftId ? 'Delegado asignado' : 'Mesa liberada')
        await this.load()
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar delegado')
      } finally {
        this.savingMesaId = null
      }
    },

    async clearMesa (mesa) {
      mesa.delegadoDraftId = null
      await this.saveMesa(mesa)
    },

    async createDelegadoRapido () {
      this.savingDelegado = true
      try {
        const payload = {
          ...this.delegadoForm,
          role: 'Delegado de Mesa'
        }
        const response = await this.$axios.post('users', payload)
        const created = response?.data || {}

        this.$alert.success(`Delegado creado${created.username ? `: ${created.username}` : ''}`)
        this.delegadoDialog = false
        await this.load()
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo crear el delegado')
      } finally {
        this.savingDelegado = false
      }
    },

    async openPrint (type) {
      this.printing = true
      const params = new URLSearchParams()
      const payload = this.buildLoadParams()

      Object.entries(payload).forEach(([key, value]) => {
        if (value != null && value !== '') {
          params.set(key, value)
        }
      })

      try {
        const response = await this.$axios.get(`admin/mapa-recintos/print/${type}`, {
          params: Object.fromEntries(params.entries()),
          responseType: 'blob'
        })

        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        window.open(url, '_blank', 'noopener,noreferrer')
        window.setTimeout(() => window.URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo generar la impresión')
      } finally {
        this.printing = false
      }
    }
  }
}
</script>
