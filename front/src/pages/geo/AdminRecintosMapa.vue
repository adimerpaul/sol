<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- HEADER -->
      <q-card-section class="row items-center">
<!--        <q-avatar rounded size="42px" class="bg-blue-1">-->
<!--          <q-icon name="map" class="text-primary" />-->
<!--        </q-avatar>-->

        <div class="col-12">
          <div class="text-h6 text-weight-bold">Actualización de Mapas • Recintos</div>
          <div class="text-caption text-grey-7">
            Filtra por provincia/municipio/localidad • Selecciona recinto • Completa distrito y coordenadas (lat/lng)
          </div>
        </div>

        <div class="col-auto row items-center q-gutter-sm">
          <q-toggle v-model="onlyMissing" label="Solo faltantes" />
          <q-btn outline color="primary" icon="refresh" label="Actualizar" no-caps :loading="loading" @click="loadRecintos" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-none">
        <div class="row">
          <div class="col-12 col-md-4">
            <div class="q-pa-sm">

              <!-- filtros -->
              <q-card flat bordered class="bg-grey-1 q-pa-sm q-mb-sm">
                <div class="row q-col-gutter-sm">
                  <div class="col-12">
                    <q-select
                      v-model="provinciaId"
                      dense outlined clearable
                      label="Provincia"
                      :options="provOptions"
                      option-label="label"
                      option-value="value"
                      emit-value map-options
                      :disable="loadingCatalogo"
                      @update:modelValue="onProvinciaChange"
                    />
                  </div>

                  <div class="col-12">
                    <q-select
                      v-model="municipioId"
                      dense outlined clearable
                      label="Municipio"
                      :options="munOptions"
                      option-label="label"
                      option-value="value"
                      emit-value map-options
                      :disable="!provinciaId || loadingMunicipios"
                      @update:modelValue="onMunicipioChange"
                    />
                  </div>

                  <div class="col-12">
                    <q-select
                      v-model="localidadId"
                      dense outlined clearable
                      label="Localidad"
                      :options="locOptions"
                      option-label="label"
                      option-value="value"
                      emit-value map-options
                      :disable="!municipioId || loadingLocalidades"
                      @update:modelValue="loadRecintos"
                    />
                  </div>

                  <div class="col-12">
                    <q-input v-model="search" dense outlined clearable placeholder="Buscar recinto..." debounce="350" @update:modelValue="loadRecintos">
                      <template v-slot:append><q-icon name="search" /></template>
                    </q-input>
                  </div>
                </div>
              </q-card>

              <!-- stats -->
              <div class="row q-col-gutter-sm q-mb-sm">
                <div class="col-6">
                  <q-card flat bordered class="q-pa-sm">
                    <div class="text-caption text-grey-7">Total</div>
                    <div class="text-h6 text-weight-bold">{{ recintos.length }}</div>
                  </q-card>
                </div>
                <div class="col-6">
                  <q-card flat bordered class="q-pa-sm">
                    <div class="text-caption text-grey-7">Faltantes</div>
                    <div class="text-h6 text-weight-bold text-orange">
                      {{ missingCount }}
                    </div>
                  </q-card>
                </div>
              </div>

              <!-- lista recintos -->
              <q-list bordered separator>
                <q-item
                  v-for="r in recintos"
                  :key="r.id"
                  clickable
                  @click="selectRecinto(r)"
                  :active="selected?.id === r.id"
                  active-class="bg-blue-1"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      {{ r.nombre }}
                    </q-item-label>
                    <q-item-label caption class="text-grey-7 ellipsis">
                      {{ r.localidad?.nombre }} • {{ r.municipio?.nombre }} • {{ r.provincia?.nombre }}
                    </q-item-label>

                    <div class="row q-gutter-xs q-mt-xs">
                      <q-badge v-if="r.missing" color="orange" outline>
                        Falta lat/lng
                      </q-badge>
                      <q-badge v-else color="green" outline>
                        OK
                      </q-badge>
                      <q-badge v-if="r.distrito" color="primary" outline>
                        Distrito: {{ r.distrito }}
                      </q-badge>
                    </div>
                  </q-item-section>

                  <q-item-section side>
                    <q-icon :name="r.missing ? 'visibility_off' : 'visibility'" :color="r.missing ? 'orange' : 'green'" />
                  </q-item-section>
                </q-item>

                <q-item v-if="!loading && recintos.length === 0">
                  <q-item-section class="text-grey-7">Sin recintos</q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <div class="q-pa-sm">
              <div v-if="!selected" class="text-grey-7 q-pa-md">
                Selecciona un recinto de la lista para editar sus coordenadas.
              </div>

              <div v-else>
                <div class="row items-center q-col-gutter-sm q-mb-sm">
                  <div class="col">
                    <div class="text-subtitle1 text-weight-bold">
                      {{ selected.nombre }}
                    </div>
                    <div class="text-caption text-grey-7">
                      {{ selected.localidad?.nombre }} • {{ selected.municipio?.nombre }} • {{ selected.provincia?.nombre }}
                    </div>
                  </div>

                  <div class="col-auto row items-center q-gutter-sm">
                    <q-badge outline :color="selected.missing ? 'warning' : 'positive'">
                      {{ selected.missing ? 'FALTANTE' : 'COMPLETO' }}
                    </q-badge>

                    <q-btn
                      color="positive"
                      icon="save"
                      label="Guardar"
                      no-caps
                      :loading="saving"
                      @click="save"
                    />
                  </div>
                </div>

                <q-card flat bordered class="q-pa-sm q-mb-sm bg-grey-1">
                  <div class="row q-col-gutter-sm">
                    <div class="col-12 col-md-6">
                      <q-input v-model="form.distrito" dense outlined label="Distrito (opcional)" />
                    </div>
                    <div class="col-12 col-md-6">
                      <q-input v-model="form.circunscripcion" dense outlined label="Circunscripción (opcional)" />
                    </div>

                    <div class="col-12 col-md-6">
                      <q-input v-model.number="form.latitud" dense outlined label="Latitud" />
                    </div>
                    <div class="col-12 col-md-6">
                      <q-input v-model.number="form.longitud" dense outlined label="Longitud" />
                    </div>
                  </div>
                </q-card>

                <!-- MAPA -->
                <q-card flat bordered class="q-pa-sm">
                  <RecintoLeafletPicker v-model="form" />
                </q-card>
              </div>
            </div>
          </div>
        </div>
<!--        <q-splitter v-model="split" style="height: calc(100vh - 180px); min-height: 580px">-->

<!--          &lt;!&ndash; LEFT: LISTA + FILTROS &ndash;&gt;-->
<!--          <template v-slot:before>-->

<!--          </template>-->

<!--          &lt;!&ndash; RIGHT: EDITOR + MAPA &ndash;&gt;-->
<!--          <template v-slot:after>-->

<!--          </template>-->

<!--        </q-splitter>-->
      </q-card-section>

      <q-inner-loading :showing="loading || loadingCatalogo || loadingMunicipios || loadingLocalidades">
        <q-spinner />
      </q-inner-loading>

    </q-card>
  </q-page>
</template>

<script>
import RecintoLeafletPicker from 'components/RecintoLeafletPicker.vue'

export default {
  name: 'AdminRecintosMapaPage',
  components: { RecintoLeafletPicker },

  data () {
    return {
      split: 38,

      loading: false,
      saving: false,

      loadingCatalogo: false,
      loadingMunicipios: false,
      loadingLocalidades: false,

      // filtros
      provinciaId: 57,
      municipioId: 191,
      localidadId: 1988,
      search: '',
      onlyMissing: false,

      provOptions: [],
      munOptions: [],
      locOptions: [],

      recintos: [],
      selected: null,

      form: {
        distrito: null,
        circunscripcion: null,
        latitud: null,
        longitud: null
      }
    }
  },

  computed: {
    missingCount () {
      return (this.recintos || []).filter(r => r.missing).length
    }
  },

  mounted () {
    this.loadCatalogo()
    this.loadMunicipios()
    this.loadLocalidades()
  },

  methods: {
    async loadCatalogo () {
      this.loadingCatalogo = true
      try {
        const r = await this.$axios.get('mapas/catalogo')
        const provincias = r.data?.provincias || []
        this.provOptions = provincias.map(p => ({ value: p.id, label: p.nombre }))
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar catálogo')
      } finally {
        this.loadingCatalogo = false
      }

      // primera carga recintos (sin filtros)
      await this.loadRecintos()
    },

    async onProvinciaChange () {
      this.municipioId = null
      this.localidadId = null
      this.munOptions = []
      this.locOptions = []
      if (!this.provinciaId) {
        await this.loadRecintos()
        return
      }
      await this.loadMunicipios()
      await this.loadRecintos()
    },

    async onMunicipioChange () {
      this.localidadId = null
      this.locOptions = []
      if (!this.municipioId) {
        await this.loadRecintos()
        return
      }
      await this.loadLocalidades()
      await this.loadRecintos()
    },

    async loadMunicipios () {
      this.loadingMunicipios = true
      try {
        const r = await this.$axios.get(`mapas/provincias/${this.provinciaId}/municipios`)
        const items = Array.isArray(r.data) ? r.data : []
        this.munOptions = items.map(m => ({ value: m.id, label: m.nombre }))
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar municipios')
      } finally {
        this.loadingMunicipios = false
      }
    },

    async loadLocalidades () {
      this.loadingLocalidades = true
      try {
        const r = await this.$axios.get(`mapas/municipios/${this.municipioId}/localidades`)
        const items = Array.isArray(r.data) ? r.data : []
        this.locOptions = items.map(l => ({ value: l.id, label: l.nombre }))
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar localidades')
      } finally {
        this.loadingLocalidades = false
      }
    },

    async loadRecintos () {
      this.loading = true
      try {
        const r = await this.$axios.get('mapas/recintos', {
          params: {
            provincia_id: this.provinciaId,
            municipio_id: this.municipioId,
            localidad_id: this.localidadId,
            search: this.search,
            only_missing: this.onlyMissing ? 1 : 0
          }
        })
        this.recintos = Array.isArray(r.data) ? r.data : []

        // si el seleccionado ya no está, limpiar
        if (this.selected?.id) {
          const still = this.recintos.find(x => x.id === this.selected.id)
          if (!still) {
            this.selected = null
          } else {
            this.selected = still
          }
        }
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar recintos')
      } finally {
        this.loading = false
      }
    },

    selectRecinto (r) {
      this.selected = { ...r }
      this.form = {
        distrito: r.distrito ?? null,
        circunscripcion: r.circunscripcion ?? null,
        latitud: r.latitud ?? null,
        longitud: r.longitud ?? null
      }
    },

    async save () {
      if (!this.selected?.id) return

      this.saving = true
      try {
        await this.$axios.put(`mapas/recintos/${this.selected.id}`, {
          distrito: this.form.distrito,
          circunscripcion: this.form.circunscripcion,
          latitud: this.form.latitud,
          longitud: this.form.longitud
        })

        this.$alert?.success('✅ Recinto actualizado')
        await this.loadRecintos()
        this.saving = false

        // refrescar seleccionado con el nuevo data
        // const again = this.recintos.find(x => x.id === this.selected.id)
        // if (again) this.selectRecinto(again)
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar')
      }
    }
  }
}
</script>
