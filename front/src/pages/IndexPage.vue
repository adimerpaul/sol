<template>
  <q-page class="q-pa-md bg-grey-3">
    <q-card flat bordered class="bg-white">

      <!-- HEADER -->
      <q-card-section class="row items-center q-col-gutter-md">
        <div class="col">
          <div class="text-h6 text-weight-bold">Dashboard Elecciones</div>
          <div class="text-caption text-grey-7">
            Resumen en tiempo real • Totales por partido • Gráficos ApexCharts
          </div>
        </div>

        <div class="col-auto">
          <q-btn
            color="primary"
            icon="refresh"
            label="Actualizar"
            no-caps
            :loading="loading"
            @click="loadResumen"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- FILTROS -->
      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-md-3">
            <q-input v-model.number="filters.pais_id" dense outlined label="pais_id (opcional)" />
          </div>
          <div class="col-12 col-md-3">
            <q-input v-model.number="filters.departamento_id" dense outlined label="departamento_id (opcional)" />
          </div>
          <div class="col-12 col-md-3">
            <q-input v-model.number="filters.municipio_id" dense outlined label="municipio_id (opcional)" />
          </div>
          <div class="col-12 col-md-3">
            <q-toggle v-model="filters.solo_realizado" label="Solo REALIZADO" />
          </div>
        </div>
      </q-card-section>

      <!-- STATS -->
      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-card flat bordered class="q-pa-md">
              <div class="text-caption text-grey-7">Votos totales</div>
              <div class="text-h5 text-weight-bold">{{ stats.votos_totales }}</div>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card flat bordered class="q-pa-md">
              <div class="text-caption text-grey-7">Mesas (REALIZADO + PENDIENTE)</div>
              <div class="text-h5 text-weight-bold">{{ stats.mesas_total }}</div>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card flat bordered class="q-pa-md">
              <div class="text-caption text-grey-7">Mesas REALIZADAS</div>
              <div class="text-h5 text-weight-bold text-positive">{{ stats.mesas_realizadas }}</div>
            </q-card>
          </div>

          <div class="col-12 col-md-3">
            <q-card flat bordered class="q-pa-md">
              <div class="text-caption text-grey-7">Mesas PENDIENTES</div>
              <div class="text-h5 text-weight-bold text-warning">{{ stats.mesas_pendientes }}</div>
            </q-card>
          </div>
        </div>

        <q-banner v-if="ganador" class="q-mt-md bg-blue-1 text-blue-10" rounded>
          <template v-slot:avatar>
            <q-icon name="emoji_events" />
          </template>

          <div class="text-subtitle2">
            Va ganando: <b>{{ ganador.sigla }}</b> — {{ ganador.nombre }}
            <q-badge outline color="primary" class="q-ml-sm">{{ ganador.votos }} votos</q-badge>
          </div>
        </q-banner>
      </q-card-section>

      <q-separator />

      <!-- CHARTS -->
      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-md">
          <!-- PIE -->
          <div class="col-12 col-lg-5">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">Distribución de votos (Pie)</div>
              <apexchart
                type="pie"
                height="340"
                :options="pieOptions"
                :series="pieSeries"
              />
            </q-card>
          </div>

          <!-- BAR -->
          <div class="col-12 col-lg-7">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">Ranking por partido (Barras)</div>
              <apexchart
                type="bar"
                height="340"
                :options="barOptions"
                :series="barSeries"
              />
            </q-card>
          </div>
        </div>

        <!-- TABLA TOP -->
        <q-card flat bordered class="q-mt-md">
          <q-card-section class="row items-center">
            <div class="text-subtitle2 text-weight-bold">Top partidos</div>
            <q-space />
            <q-badge outline color="grey-8">{{ ranking.length }} partidos</q-badge>
          </q-card-section>

          <q-separator />

          <q-markup-table flat>
            <thead>
            <tr>
              <th class="text-left">#</th>
              <th class="text-left">Sigla</th>
              <th class="text-left">Nombre</th>
              <th class="text-right">Votos</th>
              <th class="text-right">%</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(r, i) in ranking" :key="r.id">
              <td class="text-left">{{ i + 1 }}</td>
              <td class="text-left"><b>{{ r.sigla }}</b></td>
              <td class="text-left">{{ r.nombre }}</td>
              <td class="text-right">{{ r.votos }}</td>
              <td class="text-right">{{ porcentaje(r.votos) }}%</td>
            </tr>
            </tbody>
          </q-markup-table>
        </q-card>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'IndexPage',

  data () {
    return {
      loading: false,

      filters: {
        pais_id: null,
        departamento_id: null,
        municipio_id: null,
        solo_realizado: true
      },

      stats: {
        votos_totales: 0,
        mesas_total: 0,
        mesas_realizadas: 0,
        mesas_pendientes: 0
      },

      ganador: null,
      ranking: []
    }
  },

  computed: {
    pieSeries () {
      return this.ranking.map(r => Number(r.votos || 0))
    },

    pieOptions () {
      return {
        labels: this.ranking.map(r => r.sigla),
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
      }
    },

    barSeries () {
      return [{ name: 'Votos', data: this.ranking.map(r => Number(r.votos || 0)) }]
    },

    barOptions () {
      return {
        chart: { toolbar: { show: false } },
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 6
          }
        },
        xaxis: {
          categories: this.ranking.map(r => `${r.sigla}`)
        },
        dataLabels: { enabled: true }
      }
    }
  },

  async mounted () {
    await this.loadResumen()
  },

  methods: {
    porcentaje (votos) {
      const total = Number(this.stats.votos_totales || 0)
      if (!total) return '0.00'
      return ((Number(votos || 0) * 100) / total).toFixed(2)
    },

    async loadResumen () {
      this.loading = true
      try {
        const params = {
          ...this.filters
        }

        // limpia null para no mandar basura
        Object.keys(params).forEach(k => {
          if (params[k] === null || params[k] === '' || typeof params[k] === 'undefined') {
            delete params[k]
          }
        })

        const res = await this.$axios.get('dashboard/elecciones/resumen', { params })
        const data = res.data || {}

        this.stats = data.stats || this.stats
        this.ganador = data.ganador || null
        this.ranking = Array.isArray(data.ranking) ? data.ranking : []
      } catch (e) {
        this.$q.notify({
          type: 'negative',
          message: e?.response?.data?.message || 'No se pudo cargar el dashboard'
        })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
