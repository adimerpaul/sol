<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <q-card-section>
        <div class="text-h6 text-weight-bold">
          Asignación de Jefes por Recinto (Mapa)
<!--          btn atulizar-->
          <q-btn icon="refresh" round dense flat class="q-ml-sm" @click="load" />
        </div>
        <div class="text-caption text-grey-7">Selecciona un recinto en el mapa y asigna su jefe</div>

        <!-- chips -->
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

        <!-- ✅ filtro rápido -->
        <div class="row q-col-gutter-sm q-mt-sm">
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
              label="Buscar recinto rápido..."
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

              <!-- cómo se ve cada opción -->
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
                    <q-badge
                      outline
                      :color="scope.opt.okDelegados ? 'positive' : 'negative'"
                    >
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
                {{ selected?.jefe?.length ? `Actual: ${selected.jefe[0].name}` : 'Sin jefe asignado' }}
              </q-badge>
              <div class="row q-gutter-xs q-mt-xs">
                <q-badge outline color="grey-8">Mesas: {{ selected.mesas_total }}</q-badge>
                <q-badge outline color="primary">Asignadas: {{ selected.mesas_asignadas }}</q-badge>
                <q-badge outline :color="selected.mesas_faltan > 0 ? 'negative' : 'positive'">
                  {{ selected.mesas_faltan > 0 ? `Faltan ${selected.mesas_faltan}` : 'Delegados OK' }}
                </q-badge>
              </div>

              <q-select
                class="q-mt-md"
                v-model="jefeId"
                :options="jefes"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Jefe de Recinto"
                dense outlined
              />

              <q-btn
                color="primary"
                icon="save"
                label="Asignar"
                class="q-mt-sm full-width"
                :disable="!jefeId"
                @click="save"
              />
                
            </div>
          </div>
        </div>
      </q-card-section>

    </q-card>
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
      selected: null,
      jefeId: null,

      // ✅ filtro
      recintoPick: null,
      recintosOptions: [],
      focusRecinto: null
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    async load () {
      const [r, j] = await Promise.all([
        this.$axios.get('admin/mapa-recintos/recintos'),
        this.$axios.get('admin/mapa-recintos/jefes')
      ])

      this.recintos = Array.isArray(r.data) ? r.data : []
      this.jefes = Array.isArray(j.data) ? j.data : []

      // construir options inicial
      this.recintosOptions = this.buildOptions(this.recintos)
    },

    buildOptions (list) {
      return (list || []).map(x => {
        const jefe = x?.jefe?.[0]
        const jefeNombre = jefe ? `${jefe.name} (${jefe.username})` : 'Sin jefe asignado'
        const tieneJefe = !!jefe
        const mesasTotal = Number(x?.mesas_total || 0)
        const mesasAsignadas = Number(x?.mesas_asignadas || 0)
        const okDelegados = tieneJefe && (mesasTotal === 0 || mesasAsignadas >= mesasTotal)

        return {
          label: `${x.nombre} — ${jefeNombre}`, // para búsqueda rápida
          value: x.id,
          nombre: x.nombre,
          jefeNombre,
          tieneJefe,
          mesas_total: mesasTotal,
          mesas_asignadas: mesasAsignadas,
          okDelegados
        }
      })
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
          o.jefeNombre.toLowerCase().includes(needle)
        )
      })
    },

    onPickRecinto (id) {
      if (!id) {
        this.focusRecinto = null
        return
      }

      const r = (this.recintos || []).find(x => x.id === id)
      if (!r) return

      // seleccionar panel derecho
      this.onSelectRecinto(r)

      // pedir al mapa que enfoque ese recinto
      this.focusRecinto = {
        id: r.id,
        latitud: r.latitud,
        longitud: r.longitud
      }
    },

    onSelectRecinto (recinto) {
      this.selected = { ...recinto }
      this.jefeId = recinto.jefe?.[0]?.id ?? null

      // sincroniza el combo con selección de mapa
      this.recintoPick = recinto.id
    },

    async save () {
      await this.$axios.put(
        `admin/mapa-recintos/recintos/${this.selected.id}/jefe`,
        { jefe_id: this.jefeId }
      )
      this.$alert.success('Jefe asignado')
      await this.load()

      // re-seleccionar el mismo recinto con data actualizado
      const again = (this.recintos || []).find(x => x.id === this.selected.id)
      if (again) {
        this.onSelectRecinto(again)
        this.focusRecinto = { id: again.id, latitud: again.latitud, longitud: again.longitud }
      }
    }
  }
}
</script>
