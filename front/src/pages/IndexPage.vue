<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section class="row items-center">
        <div class="col">
          <div class="text-h6 text-weight-bold">Dashboard Graficos</div>
          <div class="text-caption text-grey-7">
            Votos validos por partido y mesas faltantes en tiempo real
          </div>
        </div>
        <div class="col-auto row items-center q-gutter-sm">
          <q-chip outline color="primary">Validos: {{ votosValidosTotal }}</q-chip>
          <q-chip outline color="orange">Mesas faltantes: {{ mesas.faltantes }}</q-chip>
          <q-btn
            color="primary"
            icon="refresh"
            label="Actualizar"
            no-caps
            :loading="loading"
            @click="loadGraficos"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pa-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-lg-6">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">
                Votos validos por partido (Torta)
              </div>
              <apexchart
                type="pie"
                height="320"
                :options="pieValidosOptions"
                :series="pieValidosSeries"
              />
            </q-card>
          </div>

          <div class="col-12 col-lg-6">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">
                Votos validos por partido (Histograma)
              </div>
              <apexchart
                type="bar"
                height="320"
                :options="barValidosOptions"
                :series="barValidosSeries"
              />
            </q-card>
          </div>

          <div class="col-12 col-lg-6">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">
                Mesas: con resultado vs faltantes (Torta)
              </div>
              <apexchart
                type="pie"
                height="320"
                :options="pieMesasOptions"
                :series="pieMesasSeries"
              />
            </q-card>
          </div>

          <div class="col-12 col-lg-6">
            <q-card flat bordered class="q-pa-sm">
              <div class="text-subtitle2 text-weight-bold q-pa-sm">
                Mesas: con resultado vs faltantes (Histograma)
              </div>
              <apexchart
                type="bar"
                height="320"
                :options="barMesasOptions"
                :series="barMesasSeries"
              />
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
import { io } from 'socket.io-client'

const FALLBACK_COLORS = [
  '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#e53935',
  '#00897b', '#6d4c41', '#3949ab', '#546e7a', '#7cb342'
]

export default {
  name: 'IndexPage',

  data () {
    return {
      loading: false,
      ranking: [],
      votosValidosTotal: 0,
      mesas: {
        total: 0,
        con_resultado: 0,
        faltantes: 0
      },
      socket: null,
      socketRefreshTimer: null
    }
  },

  computed: {
    rankingColors () {
      return this.ranking.map((r, i) => r.color || FALLBACK_COLORS[i % FALLBACK_COLORS.length])
    },

    pieValidosSeries () {
      return this.ranking.map(r => Number(r.votos_validos || 0))
    },
    pieValidosOptions () {
      return {
        labels: this.ranking.map(r => r.sigla || '-'),
        colors: this.rankingColors,
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
      }
    },

    barValidosSeries () {
      return [{ name: 'Votos validos', data: this.ranking.map(r => Number(r.votos_validos || 0)) }]
    },
    barValidosOptions () {
      return {
        chart: { toolbar: { show: false } },
        colors: ['#1e88e5'],
        plotOptions: {
          bar: {
            horizontal: false,
            borderRadius: 5,
            columnWidth: '55%'
          }
        },
        xaxis: { categories: this.ranking.map(r => r.sigla || '-') },
        dataLabels: { enabled: true }
      }
    },

    pieMesasSeries () {
      return [
        Number(this.mesas.con_resultado || 0),
        Number(this.mesas.faltantes || 0)
      ]
    },
    pieMesasOptions () {
      return {
        labels: ['Con resultado', 'Faltantes'],
        colors: ['#43a047', '#fb8c00'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true }
      }
    },

    barMesasSeries () {
      return [{
        name: 'Mesas',
        data: [
          Number(this.mesas.con_resultado || 0),
          Number(this.mesas.faltantes || 0)
        ]
      }]
    },
    barMesasOptions () {
      return {
        chart: { toolbar: { show: false } },
        colors: ['#546e7a'],
        plotOptions: {
          bar: {
            horizontal: false,
            borderRadius: 5,
            columnWidth: '45%'
          }
        },
        xaxis: { categories: ['Con resultado', 'Faltantes'] },
        dataLabels: { enabled: true }
      }
    }
  },

  async mounted () {
    await this.loadGraficos()
    this.connectSocket()
  },

  beforeUnmount () {
    const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
    if (this.socketRefreshTimer) {
      clearTimeout(this.socketRefreshTimer)
      this.socketRefreshTimer = null
    }
    if (this.socket) {
      this.socket.off(socketEvent)
      this.socket.disconnect()
      this.socket = null
    }
  },

  methods: {
    connectSocket () {
      const socketUrl = import.meta.env.VITE_API_SOCKET
      const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
      if (!socketUrl) return

      this.socket = io(socketUrl, {
        transports: ['websocket', 'polling'],
        reconnection: true
      })

      this.socket.on(socketEvent, (evt) => {
        this.onSocketVotacion(evt)
      })
    },

    onSocketVotacion (evt) {
      const data = typeof evt === 'string' ? { message: evt } : (evt || {})
      const title = data.title || 'Nuevo dato registrado'
      const caption = data.message || 'Dashboard actualizado'

      if (this.$alert?.info) {
        this.$alert.info(title, caption)
      } else {
        this.$q.notify({ type: 'info', message: title, caption, position: 'top' })
      }

      if (this.socketRefreshTimer) clearTimeout(this.socketRefreshTimer)
      this.socketRefreshTimer = setTimeout(() => {
        this.loadGraficos()
      }, 400)
    },

    async loadGraficos () {
      this.loading = true
      try {
        const res = await this.$axios.get('dashboard/graficos')
        const data = res.data || {}

        this.votosValidosTotal = Number(data.votos_validos_total || 0)
        this.ranking = Array.isArray(data.ranking_validos) ? data.ranking_validos : []
        this.mesas = {
          total: Number(data?.mesas?.total || 0),
          con_resultado: Number(data?.mesas?.con_resultado || 0),
          faltantes: Number(data?.mesas?.faltantes || 0)
        }
      } catch (e) {
        this.$q.notify({
          type: 'negative',
          message: e?.response?.data?.message || 'No se pudo cargar graficos'
        })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
