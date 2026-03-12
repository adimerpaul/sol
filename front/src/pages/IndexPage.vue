<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section class="row items-center">
        <div class="col-12 col-md-auto">
          <div class="text-h6 text-weight-bold">Dashboard Graficos</div>
          <div class="text-caption text-grey-7">
            Votos por categoria y mesas faltantes en tiempo real (Departamento de Oruro)
          </div>
        </div>
        <div class="col-auto row items-center q-gutter-sm">
          <q-chip outline color="primary">Validos: {{ votosValidosTotal }}</q-chip>
          <q-chip outline color="secondary">Mesas Oruro: {{ mesas.total }}</q-chip>
          <q-chip outline color="positive">Con resultado: {{ mesas.con_resultado }}</q-chip>
          <q-chip outline color="orange">Mesas faltantes: {{ mesas.faltantes }}</q-chip>
          <q-btn color="primary" icon="refresh" round dense :loading="loading" @click="loadGraficos" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pb-none">
        <div class="row q-col-gutter-sm items-center">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.provincia_id"
              :options="provinciaOptions"
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
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.municipio_id"
              :options="municipioOptions"
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
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.localidad_id"
              :options="localidadOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              dense
              outlined
              clearable
              label="Localidad"
              :disable="!filters.municipio_id"
              @update:model-value="loadGraficos"
            />
          </div>
          <div class="col-12 col-md-auto">
            <q-btn flat color="grey-7" no-caps label="Limpiar" @click="clearFilters" />
          </div>
        </div>
      </q-card-section>

      <q-card-section class="q-pa-md">
        <div
          v-for="card in chartCards"
          :key="`row-${card.key}`"
          class="category-block q-mb-md"
        >
          <div class="row items-center justify-between q-mb-sm">
            <div class="text-subtitle1 text-weight-bold">{{ card.label }}</div>
            <q-chip dense outline color="primary">Total: {{ card.total }}</q-chip>
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-card flat bordered class="q-pa-sm full-height chart-modern">
                <apexchart
                  type="pie"
                  height="280"
                  :options="pieOptions(card)"
                  :series="card.series"
                />
              </q-card>
            </div>
            <div class="col-12 col-md-6">
              <q-card flat bordered class="q-pa-sm full-height chart-modern">
                <apexchart
                  type="bar"
                  height="280"
                  :options="barOptions(card)"
                  :series="[{ name: card.label, data: card.series }]"
                />
              </q-card>
            </div>
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
const CATEGORY_DEFS = [
  { key: 'alcalde', label: 'Alcalde', valueField: 'votos_alcalde' },
  { key: 'concejal', label: 'Concejal', valueField: 'votos_concejal' },
  { key: 'gobernador', label: 'Gobernador', valueField: 'votos_gobernador' },
  { key: 'asambleista_distrito', label: 'Asambleista por Territorio', valueField: 'votos_asambleista_distrito' },
  { key: 'asambleista_poblacion', label: 'Asambleista por Poblacion', valueField: 'votos_asambleista_poblacion' }
]

export default {
  name: 'IndexPage',

  data () {
    return {
      loading: false,
      ranking: [],
      votosValidosTotal: 0,
      categorias: {},
      categoryRankings: {
        alcalde: [],
        concejal: [],
        gobernador: [],
        asambleista_distrito: [],
        asambleista_poblacion: []
      },
      mesas: {
        total: 0,
        con_resultado: 0,
        faltantes: 0
      },
      filters: {
        provincia_id: null,
        municipio_id: null,
        localidad_id: null
      },
      geoOptions: {
        provincias: [],
        municipios: [],
        localidades: []
      },
      socket: null,
      socketRefreshTimer: null
    }
  },

  computed: {
    provinciaOptions () {
      return (this.geoOptions.provincias || []).map(p => ({
        label: p.nombre,
        value: p.id
      }))
    },
    municipioOptions () {
      return (this.geoOptions.municipios || []).map(m => ({
        label: m.nombre,
        value: m.id
      }))
    },
    localidadOptions () {
      return (this.geoOptions.localidades || []).map(l => ({
        label: l.nombre,
        value: l.id
      }))
    },
    chartCards () {
      return CATEGORY_DEFS.map(def => {
        const ranking = Array.isArray(this.categoryRankings[def.key]) ? this.categoryRankings[def.key] : []
        return {
          ...def,
          labels: ranking.map(r => this.toNameCase(r.sigla || '-')),
          series: ranking.map(r => Number(r[def.valueField] || 0)),
          colors: ranking.map((r, i) => r.color || FALLBACK_COLORS[i % FALLBACK_COLORS.length]),
          total: Number(this?.categorias?.[def.key]?.total || 0)
        }
      })
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
    onProvinciaChange () {
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.loadGraficos()
    },
    onMunicipioChange () {
      this.filters.localidad_id = null
      this.loadGraficos()
    },
    clearFilters () {
      this.filters.provincia_id = null
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.loadGraficos()
    },
    toNameCase (text) {
      const value = String(text || '').trim().toLowerCase()
      if (!value) return '-'
      return value.charAt(0).toUpperCase() + value.slice(1)
    },
    pieOptions (card) {
      return {
        chart: {
          id: `pie-${card.key}`,
          toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
          animations: { enabled: true, dynamicAnimation: { speed: 600 } }
        },
        labels: card.labels,
        colors: card.colors,
        legend: {
          position: 'right',
          horizontalAlign: 'left',
          fontSize: '11px',
          width: 130
        },
        dataLabels: {
          enabled: true,
          formatter: function (val, opts) {
            const label = opts?.w?.globals?.labels?.[opts.seriesIndex] || ''
            return `${label} ${Number(val || 0).toFixed(1)}%`
          },
          style: { fontSize: '10px', fontWeight: 700 }
        },
        title: { text: '', align: 'center' }
      }
    },
    barOptions (card) {
      return {
        chart: {
          id: `bar-${card.key}`,
          toolbar: { show: true, tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false } },
          animations: { enabled: true, dynamicAnimation: { speed: 600 } }
        },
        colors: card.colors,
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: '58%',
            distributed: true
          }
        },
        xaxis: {
          categories: card.labels,
          labels: {
            style: { fontSize: '11px', fontWeight: 600 }
          }
        },
        dataLabels: {
          enabled: true,
          style: { fontSize: '12px', fontWeight: 700 },
          offsetX: 8
        },
        legend: { show: false }
      }
    },
    async loadGraficos () {
      this.loading = true
      try {
        const params = {
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined
        }
        const res = await this.$axios.get('dashboard/graficos', { params })
        const data = res.data || {}

        this.votosValidosTotal = Number(data.votos_validos_total || 0)
        this.ranking = Array.isArray(data.ranking_validos) ? data.ranking_validos : []
        this.categorias = data.categorias || {}
        this.categoryRankings = {
          alcalde: Array.isArray(data?.categorias?.alcalde?.ranking) ? data.categorias.alcalde.ranking : [],
          concejal: Array.isArray(data?.categorias?.concejal?.ranking) ? data.categorias.concejal.ranking : [],
          gobernador: Array.isArray(data?.categorias?.gobernador?.ranking) ? data.categorias.gobernador.ranking : [],
          asambleista_distrito: Array.isArray(data?.categorias?.asambleista_distrito?.ranking) ? data.categorias.asambleista_distrito.ranking : [],
          asambleista_poblacion: Array.isArray(data?.categorias?.asambleista_poblacion?.ranking) ? data.categorias.asambleista_poblacion.ranking : []
        }
        this.mesas = {
          total: Number(data?.mesas?.total || 0),
          con_resultado: Number(data?.mesas?.con_resultado || 0),
          faltantes: Number(data?.mesas?.faltantes || 0)
        }
        this.geoOptions = {
          provincias: Array.isArray(data?.options?.provincias) ? data.options.provincias : [],
          municipios: Array.isArray(data?.options?.municipios) ? data.options.municipios : [],
          localidades: Array.isArray(data?.options?.localidades) ? data.options.localidades : []
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

<style scoped>
.full-height {
  height: 100%;
}

.category-block {
  background: #f8fafc;
  border: 1px solid #e6ebf2;
  border-radius: 12px;
  padding: 12px;
}

.chart-modern {
  border-radius: 10px;
  background: #fff;
}
</style>
