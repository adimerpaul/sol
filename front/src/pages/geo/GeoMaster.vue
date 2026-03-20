<template>
  <q-page class="q-pa-sm bg-grey-2">
    <!-- HEADER -->
    <q-card flat bordered class="q-mb-sm">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12">
          <div class="text-h6 text-weight-bold">
            <q-icon
              name="fa-solid fa-map-location-dot"
              class="q-mr-sm text-primary"
            />
            Geografía Electoral
          </div>
          <div class="text-caption text-grey-7">
            CRUD por pestañas: Países, Departamentos, Provincias, Municipios,
            Localidades, Recintos y Mesas.
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
<!--        <q-tab name="paises" icon="fa-solid fa-earth-americas" label="Países" />-->
<!--        <q-tab name="departamentos" icon="fa-solid fa-sitemap" label="Departamentos" />-->
        <q-tab name="provincias" icon="fa-solid fa-network-wired" label="Provincias" />
        <q-tab name="municipios" icon="fa-solid fa-city" label="Municipios" />
        <q-tab
          name="localidades"
          icon="fa-solid fa-location-dot"
          label="Localidades"
        />
        <q-tab
          name="recintos"
          icon="fa-solid fa-school-flag"
          label="Recintos"
        />
        <q-tab name="mesas" icon="fa-solid fa-table" label="Mesas" />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated>
        <!-- ===================== PAISES ===================== -->
<!--        <q-tab-panel name="paises">-->
<!--          <crud-simple-->
<!--            title="Países"-->
<!--            endpoint="paises"-->
<!--            :columns="['ID', 'Nombre']"-->
<!--            :fields="['id', 'nombre']"-->
<!--          />-->
<!--        </q-tab-panel>-->

        <!-- ===================== DEPARTAMENTOS ===================== -->
<!--        <q-tab-panel name="departamentos">-->
<!--          <crud-simple-->
<!--            title="Departamentos"-->
<!--            endpoint="departamentos"-->
<!--            :columns="['ID', 'País', 'Nombre']"-->
<!--            :fields="['id', 'pais.nombre', 'nombre']"-->
<!--            :filters="departamentos.filters"-->
<!--            :selects="departamentoSelects"-->
<!--            @update:filters="v => (departamentos.filters = v)"-->
<!--            @filters-changed="() => {}"-->
<!--          />-->
<!--        </q-tab-panel>-->

        <!-- ===================== PROVINCIAS ===================== -->
        <q-tab-panel name="provincias">
          <crud-simple
            title="Provincias"
            endpoint="provincias"
            :columns="['ID', 'Departamento', 'Nombre']"
            :fields="['id', 'departamento.nombre', 'nombre']"
            :filters="provincias.filters"
            :selects="provinciaSelects"
            @update:filters="(v) => (provincias.filters = v)"
            @filters-changed="() => {}"
          />
<!--          <pre>{{provinciaSelects}}</pre>-->
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
            :row-actions="municipioRowActions"
            @update:filters="(v) => (municipios.filters = v)"
            @filters-changed="() => {}"
            @row-action="onMunicipioRowAction"
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
            @update:filters="(v) => (localidades.filters = v)"
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
            @update:filters="(v) => (recintos.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>

        <!-- ===================== MESAS ===================== -->
        <q-tab-panel name="mesas">
          <crud-simple
            title="Mesas"
            endpoint="mesas"
            :columns="['ID', 'Recinto', 'Mesa', 'Habilitados']"
            :fields="['id', 'recinto.nombre', 'numero_mesa', 'habilitados']"
            :filters="mesas.filters"
            :selects="mesaSelects"
            @update:filters="(v) => (mesas.filters = v)"
            @filters-changed="() => {}"
          />
        </q-tab-panel>
      </q-tab-panels>
    </q-card>

    <q-dialog v-model="partidosDialog" persistent>
      <q-card class="bg-white" style="width: 1080px; max-width: 95vw;">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col">
            <div class="text-subtitle1 text-weight-bold">Partidos por municipio</div>
            <div class="text-caption text-grey-7">
              {{ partidoConfig.municipio?.nombre || '-' }}
              <span v-if="partidoConfig.municipio?.provincia">
                · {{ partidoConfig.municipio.provincia.nombre }}
              </span>
            </div>
          </div>
          <div class="col-auto">
            <q-btn flat round dense icon="close" @click="closePartidosDialog" />
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-pa-sm">
          <q-banner dense class="bg-grey-2 text-grey-8 q-mb-xs">
            Todo inicia habilitado. Desmarca solo lo que no aplica a este municipio.
          </q-banner>

          <div class="row items-center q-col-gutter-xs q-mb-xs">
            <div class="col-auto">
              <q-btn dense flat color="primary" icon="done_all" label="Marcar todo" no-caps @click="setAllPartidos(true)" />
            </div>
            <div class="col-auto">
              <q-btn dense flat color="grey-7" icon="remove_done" label="Desmarcar todo" no-caps @click="setAllPartidos(false)" />
            </div>
          </div>

          <q-markup-table flat bordered dense class="full-width">
            <thead>
              <tr>
                <th class="text-left">Partido</th>
                <th class="text-center">
                  <div class="column items-center">
                    <span>Gob.</span>
                    <q-btn dense flat round size="8px" icon="done_all" color="primary" @click="setColumnValue('habilitado_gobernador', true)" />
                    <q-btn dense flat round size="8px" icon="remove_done" color="grey-7" @click="setColumnValue('habilitado_gobernador', false)" />
                  </div>
                </th>
                <th class="text-center">
                  <div class="column items-center">
                    <span>Asam. Pob.</span>
                    <q-btn dense flat round size="8px" icon="done_all" color="primary" @click="setColumnValue('habilitado_asambleista_poblacion', true)" />
                    <q-btn dense flat round size="8px" icon="remove_done" color="grey-7" @click="setColumnValue('habilitado_asambleista_poblacion', false)" />
                  </div>
                </th>
                <th class="text-center">
                  <div class="column items-center">
                    <span>Asam. Dist.</span>
                    <q-btn dense flat round size="8px" icon="done_all" color="primary" @click="setColumnValue('habilitado_asambleista_distrito', true)" />
                    <q-btn dense flat round size="8px" icon="remove_done" color="grey-7" @click="setColumnValue('habilitado_asambleista_distrito', false)" />
                  </div>
                </th>
                <th class="text-center">
                  <div class="column items-center">
                    <span>Alc.</span>
                    <q-btn dense flat round size="8px" icon="done_all" color="primary" @click="setColumnValue('habilitado_alcalde', true)" />
                    <q-btn dense flat round size="8px" icon="remove_done" color="grey-7" @click="setColumnValue('habilitado_alcalde', false)" />
                  </div>
                </th>
                <th class="text-center">
                  <div class="column items-center">
                    <span>Conc.</span>
                    <q-btn dense flat round size="8px" icon="done_all" color="primary" @click="setColumnValue('habilitado_concejal', true)" />
                    <q-btn dense flat round size="8px" icon="remove_done" color="grey-7" @click="setColumnValue('habilitado_concejal', false)" />
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="partido in partidoConfig.partidos" :key="partido.id">
                <td class="text-left q-py-xs" style="min-width: 250px;">
                  <div class="row items-center no-wrap q-gutter-xs">
                    <div
                      class="rounded-borders"
                      :style="{
                        width: '10px',
                        height: '30px',
                        background: partido.color || '#BDBDBD'
                      }"
                    />
                    <q-avatar square size="28px" class="bg-grey-2">
                      <q-img
                        v-if="partido.icono"
                        :src="`${$url}/../images/partidos/${partido.icono}`"
                        fit="contain"
                      />
                      <span v-else class="text-caption text-grey-7">{{ partido.sigla?.slice(0, 2) }}</span>
                    </q-avatar>
                    <div class="column">
                      <div class="text-weight-medium" style="font-size: 12px; line-height: 1.1;">{{ partido.sigla }}</div>
                      <div class="text-caption text-grey-7 ellipsis" style="max-width: 176px; line-height: 1.1;">
                        {{ partido.nombre }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="text-center q-px-none q-py-xs"><q-checkbox v-model="partido.habilitado_gobernador" dense size="xs" /></td>
                <td class="text-center q-px-none q-py-xs"><q-checkbox v-model="partido.habilitado_asambleista_poblacion" dense size="xs" /></td>
                <td class="text-center q-px-none q-py-xs"><q-checkbox v-model="partido.habilitado_asambleista_distrito" dense size="xs" /></td>
                <td class="text-center q-px-none q-py-xs"><q-checkbox v-model="partido.habilitado_alcalde" dense size="xs" /></td>
                <td class="text-center q-px-none q-py-xs"><q-checkbox v-model="partido.habilitado_concejal" dense size="xs" /></td>
              </tr>
            </tbody>
          </q-markup-table>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right" class="q-pa-sm">
          <q-btn flat dense color="grey-7" label="Cancelar" no-caps @click="closePartidosDialog" />
          <q-btn dense color="primary" label="Guardar" no-caps :loading="savingPartidos" @click="saveMunicipioPartidos" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import CrudSimple from "components/CrudSimple.vue";

export default {
  name: "GeoElectoral",
  components: { CrudSimple },

  data() {
    return {
      tab: 'provincias',
      loadingGeo: false,
      defaults: {
        pais_id: 1,          // si no usas país, déjalo null
        departamento_id: 5,     // Oruro
        provincia_id: 57,       // Cercado
        municipio_id: 191,      // Oruro
        localidad_id: 1988,     // Oruro
        recinto_id: null        // si quieres fijo, pon el id
      },
      geo: {
        paises: [],
        departamentos: [],
        provincias: [],
        municipios: [],
        localidades: [],
        recintos: [],
      },

      departamentos: { filters: { pais_id: 1 } },

      // ✅ PROVINCIAS: solo necesita departamento_id
      provincias: { filters: { pais_id: 1, departamento_id: 5 } },

      // ✅ MUNICIPIOS: depto + provincia
      municipios: { filters: { pais_id: 1, departamento_id: 5, provincia_id: null } },

      // ✅ LOCALIDADES: depto + provincia + municipio
      localidades: {
        filters: { pais_id: 1, departamento_id: 5, provincia_id: 57, municipio_id: 191 }
      },

      // ✅ RECINTOS: depto + provincia + municipio + localidad
      recintos: {
        filters: { pais_id: 1, departamento_id: 5, provincia_id: 57, municipio_id: 191, localidad_id: 1988 }
      },

      // ✅ MESAS: todo + recinto (si quieres)
      mesas: {
        filters: {
          pais_id: 1,
          departamento_id: 5,
          provincia_id: 57,
          municipio_id: 191,
          localidad_id: 1988,
          recinto_id: null
        }
      },

      partidosDialog: false,
      savingPartidos: false,
      partidoConfig: {
        municipio: null,
        partidos: [],
      }
    };
  },

  computed: {
    municipioRowActions() {
      return [
        {
          key: "partidos",
          label: "Agregar partidos",
          icon: "how_to_vote",
          colorClass: "text-indigo-7",
        },
      ];
    },

    optPaises() {
      return this.geo.paises || [];
    },

    optDepartamentosByPais() {
      return (paisId) =>
        (this.geo.departamentos || []).filter(
          (d) => !paisId || d.pais_id === paisId,
        );
    },
    optProvinciasByDepartamento() {
      return (deptoId) =>
        (this.geo.provincias || []).filter(
          (p) => !deptoId || p.departamento_id === deptoId,
        );
    },
    optMunicipiosByProvincia() {
      return (provId) =>
        (this.geo.municipios || []).filter(
          (m) => !provId || m.provincia_id === provId,
        );
    },
    optLocalidadesByMunicipio() {
      return (munId) =>
        (this.geo.localidades || []).filter(
          (l) => !munId || l.municipio_id === munId,
        );
    },
    optRecintosByLocalidad() {
      return (locId) =>
        (this.geo.recintos || []).filter(
          (r) => !locId || r.localidad_id === locId,
        );
    },

    // Configuración de selects por tab
    departamentoSelects() {
      return [
        {
          key: "pais_id",
          label: "País",
          icon: "fa-solid fa-earth-americas",
          options: this.optPaises,
          optionLabel: "nombre",
          optionValue: "id",
        },
      ];
    },

    provinciaSelects() {
      return [
        // {
        //   key: 'pais_id',
        //   label: 'País',
        //   icon: 'fa-solid fa-earth-americas',
        //   options: this.optPaises,
        //   optionLabel: 'nombre',
        //   optionValue: 'id',
        //   resets: ['departamento_id']
        // },
        {
          key: "departamento_id",
          label: "Departamento",
          icon: "fa-solid fa-sitemap",
          options: this.optDepartamentosByPais(this.provincias.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          // disable: !this.provincias.filters.pais_id
        }
      ]
    },

    municipioSelects() {
      return [
        // {
        //   key: 'pais_id',
        //   label: 'País',
        //   icon: 'fa-solid fa-earth-americas',
        //   options: this.optPaises,
        //   optionLabel: 'nombre',
        //   optionValue: 'id',
        //   resets: ['departamento_id', 'provincia_id']
        // },
        {
          key: "departamento_id",
          label: "Depto",
          icon: "fa-solid fa-sitemap",
          options: this.optDepartamentosByPais(this.municipios.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          // disable: !this.municipios.filters.pais_id,
          resets: ['provincia_id']
        },
        {
          key: "provincia_id",
          label: "Provincia",
          icon: "fa-solid fa-network-wired",
          options: this.optProvinciasByDepartamento(
            this.municipios.filters.departamento_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.municipios.filters.departamento_id,
        },
      ];
    },

    localidadSelects() {
      return [
        // {
        //   key: 'pais_id',
        //   label: 'País',
        //   icon: 'fa-solid fa-earth-americas',
        //   options: this.optPaises,
        //   optionLabel: 'nombre',
        //   optionValue: 'id',
        //   resets: ['departamento_id', 'provincia_id', 'municipio_id']
        // },
        {
          key: 'departamento_id',
          label: 'Depto',
          icon: 'fa-solid fa-sitemap',
          options: this.optDepartamentosByPais(this.localidades.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          // disable: !this.localidades.filters.pais_id,
          resets: ['provincia_id', 'municipio_id']
        },
        {
          key: "provincia_id",
          label: "Provincia",
          icon: "fa-solid fa-network-wired",
          options: this.optProvinciasByDepartamento(
            this.localidades.filters.departamento_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.localidades.filters.departamento_id,
          resets: ["municipio_id"],
        },
        {
          key: "municipio_id",
          label: "Municipio",
          icon: "fa-solid fa-city",
          options: this.optMunicipiosByProvincia(
            this.localidades.filters.provincia_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.localidades.filters.provincia_id,
        },
      ];
    },

    recintoSelects() {
      return [
        // cascada completa hasta localidad
        // {
        //   key: 'pais_id',
        //   label: 'País',
        //   icon: 'fa-solid fa-earth-americas',
        //   options: this.optPaises,
        //   optionLabel: 'nombre',
        //   optionValue: 'id',
        //   resets: ['departamento_id', 'provincia_id', 'municipio_id', 'localidad_id']
        // },
        {
          key: "departamento_id",
          label: "Depto",
          icon: "fa-solid fa-sitemap",
          options: this.optDepartamentosByPais(this.recintos.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          // disable: !this.recintos.filters.pais_id,
          resets: ['provincia_id', 'municipio_id', 'localidad_id']
        },
        {
          key: "provincia_id",
          label: "Provincia",
          icon: "fa-solid fa-network-wired",
          options: this.optProvinciasByDepartamento(
            this.recintos.filters.departamento_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.recintos.filters.departamento_id,
          resets: ["municipio_id", "localidad_id"],
        },
        {
          key: "municipio_id",
          label: "Municipio",
          icon: "fa-solid fa-city",
          options: this.optMunicipiosByProvincia(
            this.recintos.filters.provincia_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.recintos.filters.provincia_id,
          resets: ["localidad_id"],
        },
        {
          key: "localidad_id",
          label: "Localidad",
          icon: "fa-solid fa-location-dot",
          options: this.optLocalidadesByMunicipio(
            this.recintos.filters.municipio_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.recintos.filters.municipio_id,
        },
      ];
    },

    mesaSelects() {
      return [
        // cascada completa hasta localidad + recinto
        // {
        //   key: 'pais_id',
        //   label: 'País',
        //   icon: 'fa-solid fa-earth-americas',
        //   options: this.optPaises,
        //   optionLabel: 'nombre',
        //   optionValue: 'id',
        //   resets: ['departamento_id', 'provincia_id', 'municipio_id', 'localidad_id', 'recinto_id']
        // },
        {
          key: "departamento_id",
          label: "Depto",
          icon: "fa-solid fa-sitemap",
          options: this.optDepartamentosByPais(this.mesas.filters.pais_id),
          optionLabel: 'nombre',
          optionValue: 'id',
          // disable: !this.mesas.filters.pais_id,
          resets: ['provincia_id', 'municipio_id', 'localidad_id', 'recinto_id']
        },
        {
          key: "provincia_id",
          label: "Provincia",
          icon: "fa-solid fa-network-wired",
          options: this.optProvinciasByDepartamento(
            this.mesas.filters.departamento_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.mesas.filters.departamento_id,
          resets: ["municipio_id", "localidad_id", "recinto_id"],
        },
        {
          key: "municipio_id",
          label: "Municipio",
          icon: "fa-solid fa-city",
          options: this.optMunicipiosByProvincia(
            this.mesas.filters.provincia_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.mesas.filters.provincia_id,
          resets: ["localidad_id", "recinto_id"],
        },
        {
          key: "localidad_id",
          label: "Localidad",
          icon: "fa-solid fa-location-dot",
          options: this.optLocalidadesByMunicipio(
            this.mesas.filters.municipio_id,
          ),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.mesas.filters.municipio_id,
          resets: ["recinto_id"],
        },
        {
          key: "recinto_id",
          label: "Recinto",
          icon: "fa-solid fa-school-flag",
          options: this.optRecintosByLocalidad(this.mesas.filters.localidad_id),
          optionLabel: "nombre",
          optionValue: "id",
          disable: !this.mesas.filters.localidad_id,
        },
      ];
    },
  },

  mounted() {
    this.init();
  },

  methods: {
    async init() {
      await this.loadGeoOptions();
    },

    async loadGeoOptions() {
      this.loadingGeo = true;
      try {
        const r = await this.$axios.get("geo/options");
        this.geo = {
          paises: r.data?.paises || [],
          departamentos: r.data?.departamentos || [],
          provincias: r.data?.provincias || [],
          municipios: r.data?.municipios || [],
          localidades: r.data?.localidades || [],
          recintos: r.data?.recintos || [],
        };
      } catch (e) {
        this.$q.notify({
          type: "negative",
          message:
            e?.response?.data?.message || "No se pudo cargar geo/options",
        });
      } finally {
        this.loadingGeo = false;
      }
    },

    onMunicipioRowAction({ action, row }) {
      if (action?.key === "partidos") {
        this.openMunicipioPartidos(row);
      }
    },

    async openMunicipioPartidos(row) {
      this.partidoConfig = {
        municipio: null,
        partidos: [],
      };
      this.partidosDialog = true;
      this.loadingGeo = true;

      try {
        const { data } = await this.$axios.get(`municipios/${row.id}/partidos`);
        this.partidoConfig = {
          municipio: data?.municipio || row,
          partidos: Array.isArray(data?.partidos) ? data.partidos : [],
        };
      } catch (e) {
        this.partidosDialog = false;
        this.$q.notify({
          type: "negative",
          message:
            e?.response?.data?.message || "No se pudo cargar partidos del municipio",
        });
      } finally {
        this.loadingGeo = false;
      }
    },

    closePartidosDialog() {
      if (this.savingPartidos) return;
      this.partidosDialog = false;
    },

    setAllPartidos(value) {
      (this.partidoConfig.partidos || []).forEach((partido) => {
        partido.habilitado_gobernador = value;
        partido.habilitado_asambleista_poblacion = value;
        partido.habilitado_asambleista_distrito = value;
        partido.habilitado_alcalde = value;
        partido.habilitado_concejal = value;
      });
    },

    setColumnValue(key, value) {
      (this.partidoConfig.partidos || []).forEach((partido) => {
        partido[key] = value;
      });
    },

    async saveMunicipioPartidos() {
      const municipioId = this.partidoConfig?.municipio?.id;
      if (!municipioId) return;

      this.savingPartidos = true;
      try {
        await this.$axios.put(`municipios/${municipioId}/partidos`, {
          partidos: (this.partidoConfig.partidos || []).map((partido) => ({
            partido_id: partido.id,
            habilitado_gobernador: !!partido.habilitado_gobernador,
            habilitado_asambleista_poblacion: !!partido.habilitado_asambleista_poblacion,
            habilitado_asambleista_distrito: !!partido.habilitado_asambleista_distrito,
            habilitado_alcalde: !!partido.habilitado_alcalde,
            habilitado_concejal: !!partido.habilitado_concejal,
          })),
        });

        this.$q.notify({
          type: "positive",
          message: "Configuración de partidos actualizada",
        });
        this.partidosDialog = false;
      } catch (e) {
        this.$q.notify({
          type: "negative",
          message:
            e?.response?.data?.message || "No se pudo guardar la configuración",
        });
      } finally {
        this.savingPartidos = false;
      }
    },
  },
};
</script>
