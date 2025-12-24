<template>
  <q-page class="q-pa-sm bg-grey-2">

    <!-- HEADER -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
          <div class="text-h6 text-weight-bold">
            <q-icon name="fa-solid fa-map-location-dot" class="q-mr-sm text-primary" />
            Geografía Electoral
          </div>
          <div class="text-caption text-grey-7">
            CRUD por pestañas: Países, Departamentos, Provincias, Municipios, Localidades, Recintos y Mesas.
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            outline
            color="primary"
            icon="refresh"
            label="Refrescar Catálogos"
            no-caps
            :loading="loadingGeo"
            @click="loadGeoOptions"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- TABS -->
    <q-card flat bordered>
      <q-tabs
        v-model="tab"
        dense
        align="left"
        no-caps
        inline-label
        class="bg-white text-primary"
        active-color="primary"
        indicator-color="primary"
      >
        <q-tab name="paises" icon="fa-solid fa-earth-americas" label="Países" />
        <q-tab name="departamentos" icon="fa-solid fa-sitemap" label="Departamentos" />
        <q-tab name="provincias" icon="fa-solid fa-network-wired" label="Provincias" />
        <q-tab name="municipios" icon="fa-solid fa-city" label="Municipios" />
        <q-tab name="localidades" icon="fa-solid fa-location-dot" label="Localidades" />
        <q-tab name="recintos" icon="fa-solid fa-school-flag" label="Recintos" />
        <q-tab name="mesas" icon="fa-solid fa-table" label="Mesas" />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated>
        <!-- ===================== PAISES ===================== -->
        <q-tab-panel name="paises">
          <crud-simple
            title="Países"
            endpoint="paises"
            :columns="['ID', 'Nombre']"
            :fields="['id', 'nombre']"
          />
        </q-tab-panel>

        <!-- ===================== DEPARTAMENTOS ===================== -->
        <q-tab-panel name="departamentos">
          <crud-simple
            title="Departamentos"
            endpoint="departamentos"
            :columns="['ID', 'País', 'Nombre']"
            :fields="['id', 'pais.nombre', 'nombre']"
            :filters="departamentos.filters"
            :selects="departamentoSelects"
            @update:filters="v => (departamentos.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== PROVINCIAS ===================== -->
        <q-tab-panel name="provincias">
          <crud-simple
            title="Provincias"
            endpoint="provincias"
            :columns="['ID', 'Departamento', 'Nombre']"
            :fields="['id', 'departamento.nombre', 'nombre']"
            :filters="provincias.filters"
            :selects="provinciaSelects"
            @update:filters="v => (provincias.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== MUNICIPIOS ===================== -->
        <q-tab-panel name="municipios">
          <crud-simple
            title="Municipios"
            endpoint="municipios"
            :columns="['ID', 'Provincia', 'Nombre']"
            :fields="['id', 'provincia.nombre', 'nombre']"
            :filters="municipios.filters"
            :selects="municipioSelects"
            @update:filters="v => (municipios.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== LOCALIDADES ===================== -->
        <q-tab-panel name="localidades">
          <crud-simple
            title="Localidades"
            endpoint="localidades"
            :columns="['ID', 'Municipio', 'Nombre']"
            :fields="['id', 'municipio.nombre', 'nombre']"
            :filters="localidades.filters"
            :selects="localidadSelects"
            @update:filters="v => (localidades.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== RECINTOS ===================== -->
        <q-tab-panel name="recintos">
          <crud-simple
            title="Recintos"
            endpoint="recintos"
            :columns="['ID', 'Localidad', 'Nombre']"
            :fields="['id', 'localidad.nombre', 'nombre']"
            :filters="recintos.filters"
            :selects="recintoSelects"
            @update:filters="v => (recintos.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== MESAS ===================== -->
        <q-tab-panel name="mesas">
          <crud-simple
            title="Mesas"
            endpoint="mesas"
            :columns="['ID', 'Recinto', 'Mesa']"
            :fields="['id', 'recinto.nombre', 'numero_mesa']"
            :filters="mesas.filters"
            :selects="mesaSelects"
            @update:filters="v => (mesas.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>
      </q-tab-panels>
    </q-card>
  </q-page>
</template>

<script>
import CrudSimple from 'components/CrudSimple.vue'

export default {
  name: 'GeoElectoral',
  components: { CrudSimple },

  data () {
    return {
      tab: 'paises',
      loadingGeo: false,

      geo: {
        paises: [],
        departamentos: [],
        provincias: [],
        municipios: [],
        localidades: [],
        recintos: []
      },

      departamentos: { filters: { pais_id: null } },
      provincias: { filters: { pais_id: null, departamento_id: null } },
      municipios: { filters: { pais_id: null, departamento_id: null, provincia_id: null } },
      localidades: { filters: { pais_id: null, departamento_id: null, provincia_id: null, municipio_id: null } },
      recintos: { filters: { pais_id: null, departamento_id: null, provincia_id: null, municipio_id: null, localidad_id: null } },
      mesas: { filters: { pais_id: null, departamento_id: null, provincia_id: null, municipio_id: null, localidad_id: null, recinto_id: null } }
    }
  },

  computed: {
    optPaises () { return this.geo.paises || [] },

    optDepartamentosByPais () {
      return (paisId) => (this.geo.departamentos || []).filter(d => !paisId || d.pais_id === paisId)
    },
    optProvinciasByDepartamento () {
      return (deptoId) => (this.geo.provincias || []).filter(p => !deptoId || p.departamento_id === deptoId)
    },
    optMunicipiosByProvincia () {
      return (provId) => (this.geo.municipios || []).filter(m => !provId || m.provincia_id === provId)
    },
    optLocalidadesByMunicipio () {
      return (munId) => (this.geo.localidades || []).filter(l => !munId || l.municipio_id === munId)
    },
    optRecintosByLocalidad () {
      return (locId) => (this.geo.recintos || []).filter(r => !locId || r.localidad_id === locId)
    },

    // Configuración de selects por tab
    departamentoSelects () {
      return [
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id'
        }
      ]
    },

    provinciaSelects () {
      return [
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id',
          resets: ['departamento_id']
        },
        {
          key: 'departamento_id',
          label: 'Departamento',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.provincias.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.provincias.filters.pais_id
        }
      ]
    },

    municipioSelects () {
      return [
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id',
          resets: ['departamento_id', 'provincia_id']
        },
        {
          key: 'departamento_id',
          label: 'Depto',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.municipios.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.municipios.filters.pais_id,
          resets: ['provincia_id']
        },
        {
          key: 'provincia_id',
          label: 'Provincia',
          icon: 'fa-solid fa-network-wired',
          options: this.optProvinciasByDepartamento(this.municipios.filters.departamento_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.municipios.filters.departamento_id
        }
      ]
    },

    localidadSelects () {
      return [
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id',
          resets: ['departamento_id', 'provincia_id', 'municipio_id']
        },
        {
          key: 'departamento_id',
          label: 'Depto',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.localidades.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.localidades.filters.pais_id,
          resets: ['provincia_id', 'municipio_id']
        },
        {
          key: 'provincia_id',
          label: 'Provincia',
          icon: 'fa-solid fa-network-wired',
          options: this.optProvinciasByDepartamento(this.localidades.filters.departamento_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.localidades.filters.departamento_id,
          resets: ['municipio_id']
        },
        {
          key: 'municipio_id',
          label: 'Municipio',
          icon: 'fa-solid fa-city',
          options: this.optMunicipiosByProvincia(this.localidades.filters.provincia_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.localidades.filters.provincia_id
        }
      ]
    },

    recintoSelects () {
      return [
        // cascada completa hasta localidad
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id',
          resets: ['departamento_id', 'provincia_id', 'municipio_id', 'localidad_id']
        },
        {
          key: 'departamento_id',
          label: 'Depto',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.recintos.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.recintos.filters.pais_id,
          resets: ['provincia_id', 'municipio_id', 'localidad_id']
        },
        {
          key: 'provincia_id',
          label: 'Provincia',
          icon: 'fa-solid fa-network-wired',
          options: this.optProvinciasByDepartamento(this.recintos.filters.departamento_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.recintos.filters.departamento_id,
          resets: ['municipio_id', 'localidad_id']
        },
        {
          key: 'municipio_id',
          label: 'Municipio',
          icon: 'fa-solid fa-city',
          options: this.optMunicipiosByProvincia(this.recintos.filters.provincia_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.recintos.filters.provincia_id,
          resets: ['localidad_id']
        },
        {
          key: 'localidad_id',
          label: 'Localidad',
          icon: 'fa-solid fa-location-dot',
          options: this.optLocalidadesByMunicipio(this.recintos.filters.municipio_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.recintos.filters.municipio_id
        }
      ]
    },

    mesaSelects () {
      return [
        // cascada completa hasta localidad + recinto
        {
          key: 'pais_id',
          label: 'País',
          icon: 'fa-solid fa-earth-americas',
          options: this.optPaises,
          optionLabel: 'nombre',
          optionValue: 'id',
          resets: ['departamento_id', 'provincia_id', 'municipio_id', 'localidad_id', 'recinto_id']
        },
        {
          key: 'departamento_id',
          label: 'Depto',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.mesas.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.mesas.filters.pais_id,
          resets: ['provincia_id', 'municipio_id', 'localidad_id', 'recinto_id']
        },
        {
          key: 'provincia_id',
          label: 'Provincia',
          icon: 'fa-solid fa-network-wired',
          options: this.optProvinciasByDepartamento(this.mesas.filters.departamento_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.mesas.filters.departamento_id,
          resets: ['municipio_id', 'localidad_id', 'recinto_id']
        },
        {
          key: 'municipio_id',
          label: 'Municipio',
          icon: 'fa-solid fa-city',
          options: this.optMunicipiosByProvincia(this.mesas.filters.provincia_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.mesas.filters.provincia_id,
          resets: ['localidad_id', 'recinto_id']
        },
        {
          key: 'localidad_id',
          label: 'Localidad',
          icon: 'fa-solid fa-location-dot',
          options: this.optLocalidadesByMunicipio(this.mesas.filters.municipio_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.mesas.filters.municipio_id,
          resets: ['recinto_id']
        },
        {
          key: 'recinto_id',
          label: 'Recinto',
          icon: 'fa-solid fa-school-flag',
          options: this.optRecintosByLocalidad(this.mesas.filters.localidad_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          disable: !this.mesas.filters.localidad_id
        }
      ]
    }
  },

  mounted () {
    this.init()
  },

  methods: {
    async init () {
      await this.loadGeoOptions()
    },

    async loadGeoOptions () {
      this.loadingGeo = true
      try {
        const r = await this.$axios.get('geo/options')
        this.geo = {
          paises: r.data?.paises || [],
          departamentos: r.data?.departamentos || [],
          provincias: r.data?.provincias || [],
          municipios: r.data?.municipios || [],
          localidades: r.data?.localidades || [],
          recintos: r.data?.recintos || []
        }
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo cargar geo/options' })
      } finally {
        this.loadingGeo = false
      }
    }
  }
}
</script>
