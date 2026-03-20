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
          <div class="col-12 col-md-auto">
            <q-btn color="primary" icon="bar_chart" no-caps label="Ver Gráficos" @click="openChartViewer" />
          </div>
          <div class="col-12 col-md-auto">
            <q-btn color="secondary" icon="map" no-caps label="Ver Mapa" @click="openMapViewer" />
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

    <!-- Modal Visor de Gráficos Moderno -->
    <q-dialog v-model="chartViewerOpen" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 text-dark flex flex-center column">
        <q-toolbar class="bg-primary text-white full-width absolute-top">
          <q-toolbar-title class="text-weight-bold">Visor de Gráficos</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="q-pt-sm q-mt-xl full-width wrap justify-center row q-gutter-sm">
          <div class="col-12">
            <q-tabs
              v-model="viewerCategory"
              dense
              class="text-grey-8"
              active-color="primary"
              indicator-color="primary"
              align="justify"
              narrow-indicator
            >
              <q-tab v-for="cat in chartCards" :key="cat.key" :name="cat.key" :label="cat.label" />
            </q-tabs>
          </div>
          
          <div class="col-12">
             <q-tabs
              v-model="viewerChartType"
              dense
              class="text-grey-8"
              active-color="secondary"
              indicator-color="secondary"
              align="center"
              narrow-indicator
            >
              <q-tab name="pie" icon="pie_chart" label="Torta" />
              <q-tab name="bar" icon="bar_chart" label="Histograma" />
            </q-tabs>
          </div>

          <div class="col-12 col-md-11 q-mt-sm">
             <q-card flat bordered class="q-pa-md chart-modern">
                <apexchart
                  v-if="viewerChartType === 'pie'"
                  type="pie"
                  height="100%"
                  class="responsive-chart"
                  :options="activeChartCard ? pieOptionsModern(activeChartCard) : {}"
                  :series="activeChartCard ? activeChartCard.series : []"
                />
                <apexchart
                  v-if="viewerChartType === 'bar'"
                  type="bar"
                  height="100%"
                  class="responsive-chart"
                  :options="activeChartCard ? barOptionsModern(activeChartCard) : {}"
                  :series="activeChartCard ? [{ name: activeChartCard.label, data: activeChartCard.series }] : []"
                />
             </q-card>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Modal Visor de MAPA -->
    <q-dialog v-model="mapViewerOpen" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 text-dark">
        <q-toolbar class="bg-secondary text-white full-width absolute-top">
          <q-toolbar-title class="text-weight-bold">Visor de Mapa Ganadores</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="q-pt-sm q-mt-xl full-width">
          <div class="row q-col-gutter-sm">
            <div class="col-12">
              <q-tabs
                v-model="viewerCategory"
                dense
                class="text-grey-8"
                active-color="secondary"
                indicator-color="secondary"
                align="justify"
                narrow-indicator
              >
                <q-tab v-for="cat in chartCards" :key="'map-'+cat.key" :name="cat.key" :label="cat.label" />
              </q-tabs>
            </div>
            <div class="col-12">
              <q-card flat bordered class="q-pa-none overflow-hidden" style="height: calc(100vh - 180px)">
                <l-map
                  ref="mapRef"
                  :zoom="zoom"
                  :center="center"
                  :use-global-leaflet="false"
                  :options="{ attributionControl: false }"
                >
                  <l-control-layers position="topright" />
                  <l-tile-layer
                    layer-type="base"
                    name="Mapa Claro"
                    url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png"
                    attribution="&copy; <a href='https://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors &copy; <a href='https://carto.com/attributions'>CARTO</a>"
                    :max-zoom="20"
                    :visible="true"
                  />
                  <l-tile-layer
                    layer-type="base"
                    name="Google Calle"
                    url="https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
                    attribution="Map data &copy; Google"
                    :max-zoom="21"
                    :visible="false"
                  />
                  <l-tile-layer
                    layer-type="base"
                    name="Google Híbrido"
                    url="https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}"
                    attribution="Map data &copy; Google"
                    :max-zoom="21"
                    :visible="false"
                  />

                  <template v-for="r in mapData" :key="r.id">
                    <l-marker
                      v-if="r.winners[viewerCategory] && r.winners[viewerCategory].partido_id"
                      :lat-lng="[r.lat, r.lng]"
                    >
                      <l-icon
                        :icon-anchor="[10, 10]"
                        :popup-anchor="[0, -10]"
                        class-name="modern-marker-container"
                      >
                        <div 
                          class="modern-marker" 
                          :style="{ 
                            backgroundColor: r.winners[viewerCategory].color,
                            boxShadow: `0 0 12px ${r.winners[viewerCategory].color}`
                          }"
                        >
                          <div class="inner-dot"></div>
                        </div>
                      </l-icon>
                      <l-popup>
                        <div class="text-weight-bold text-primary">{{ r.nombre }}</div>
                        <q-separator q-my-xs />
                        <div class="row items-center no-wrap">
                          <q-badge :style="{backgroundColor: r.winners[viewerCategory].color}" class="q-mr-xs">Winner</q-badge>
                          <span class="text-weight-bold">{{ r.winners[viewerCategory].votos }} votos</span>
                        </div>
                      </l-popup>
                    </l-marker>
                  </template>

                </l-map>
              </q-card>
            </div>
          </div>
        </q-card-section>
        <q-inner-loading :showing="loadingMap">
          <q-spinner color="secondary" size="4em" />
        </q-inner-loading>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import { io } from 'socket.io-client'
import { LMap, LTileLayer, LMarker, LIcon, LPopup, LControlLayers } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

// Fix for default marker icons
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl   from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

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
  components: {
    LMap,
    LTileLayer,
    LMarker,
    LIcon,
    LPopup,
    LControlLayers
  },

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
      socketRefreshTimer: null,
      chartViewerOpen: false,
      viewerCategory: 'alcalde',
      viewerChartType: 'pie',

      // Map Data
      mapViewerOpen: false,
      mapData: [],
      loadingMap: false,
      zoom: 12,
      center: [-17.9647, -67.1060]
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
    },
    activeChartCard () {
      return this.chartCards.find(c => c.key === this.viewerCategory)
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
        if (this.mapViewerOpen) this.loadMapData()
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
    pieOptionsModern (card) {
      let opts = JSON.parse(JSON.stringify(this.pieOptions(card)))
      opts.legend.fontSize = '14px' // slightly smaller for base
      opts.legend.width = 250
      opts.dataLabels.style.fontSize = '14px'

      // Make responsive
      opts.responsive = [{
        breakpoint: 768, // Mobile and small tablets
        options: {
          legend: {
            position: 'bottom',
            fontSize: '12px',
            width: '100%'
          },
          dataLabels: {
            style: { fontSize: '12px' }
          }
        }
      }]

      return opts
    },
    barOptionsModern (card) {
      let opts = JSON.parse(JSON.stringify(this.barOptions(card)))
      opts.xaxis.labels.style.fontSize = '12px'
      opts.dataLabels.style.fontSize = '14px'

      // Make responsive
      opts.responsive = [{
        breakpoint: 768,
        options: {
          xaxis: {
            labels: {
              style: { fontSize: '10px', fontWeight: 500 }
            }
          },
          dataLabels: {
            style: { fontSize: '11px' }
          }
        }
      }]

      return opts
    },
    openChartViewer () {
      this.chartViewerOpen = true
      if (this.chartCards && this.chartCards.length > 0) {
        this.viewerCategory = this.chartCards[0].key
      }
    },
    openMapViewer () {
      this.mapViewerOpen = true
      this.loadMapData()
    },
    async loadMapData () {
      this.loadingMap = true
      try {
        const params = {
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined
        }
        const res = await this.$axios.get('dashboard/mapa', { params })
        this.mapData = res.data || []
      } catch (e) {
        this.$q.notify({
          type: 'negative',
          message: 'No se pudo cargar datos del mapa'
        })
      } finally {
        this.loadingMap = false
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

.responsive-chart {
  min-height: 400px;
  height: 70vh !important;
}

@media (min-width: 1024px) {
  .responsive-chart {
    height: 75vh !important;
  }
}

@media (max-width: 768px) {
  .responsive-chart {
    min-height: 350px;
    height: 60vh !important;
  }
}

/* Modern Markers */
.modern-marker-container {
  background: transparent !important;
  border: none !important;
}

.modern-marker {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  animation: pulse 2s infinite ease-in-out;
  cursor: pointer;
}

.modern-marker:hover {
  transform: scale(1.3);
  z-index: 1000 !important;
}

.inner-dot {
  width: 6px;
  height: 6px;
  background-color: white;
  border-radius: 50%;
  opacity: 0.8;
}

@keyframes pulse {
  0% {
    transform: scale(1);
    filter: brightness(1);
  }
  50% {
    transform: scale(1.1);
    filter: brightness(1.2);
  }
  100% {
    transform: scale(1);
    filter: brightness(1);
  }
}
</style>
