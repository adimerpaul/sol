<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md">
      <div :class="mapColumnClass">
        <q-card flat bordered class="overflow-hidden" :class="{ 'public-map-expanded': expandedMap }">
          <q-card-section class="row items-center q-col-gutter-sm">
            <div class="col">
              <div class="text-h6 text-weight-bold">Mapa de Recintos</div>
              <div class="text-caption text-grey-7">{{ rows.length }} recintos públicos</div>
            </div>
            <div class="col-auto">
              <q-btn
                flat
                color="primary"
                :icon="expandedMap ? 'close_fullscreen' : 'open_in_full'"
                :label="expandedMap ? 'Cerrar' : 'Expandir'"
                no-caps
                @click="expandedMap = !expandedMap"
              />
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-none public-map-shell" :style="mapSectionStyle">
            <div class="public-map-toolbar">
              <q-select
                v-model="selectedRecintoId"
                :options="recintoOptionsFiltered"
                emit-value
                map-options
                use-input
                input-debounce="0"
                dense
                outlined
                clearable
                label="Ir al recinto"
                class="public-map-select"
                @filter="filterRecintos"
                @update:model-value="onSelectRecinto"
              />
              <q-btn
                color="primary"
                icon="my_location"
                label="Dónde estoy"
                no-caps
                :loading="locating"
                @click="locateMe"
              />
            </div>

            <l-map
              ref="mapRef"
              v-model:zoom="zoom"
              :center="center"
              :use-global-leaflet="false"
              :options="{ attributionControl: false }"
              style="height: 100%; width: 100%;"
            >
              <l-control-layers position="topright" />

              <l-tile-layer
                layer-type="base"
                name="Google Calle"
                url="https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
                :max-zoom="21"
                :visible="true"
              />
              <l-tile-layer
                layer-type="base"
                name="Google Satélite"
                url="https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}"
                :max-zoom="21"
                :visible="false"
              />
              <l-tile-layer
                layer-type="base"
                name="Google Híbrido"
                url="https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}"
                :max-zoom="21"
                :visible="false"
              />

              <l-marker
                v-for="row in rows"
                :key="row.id"
                :lat-lng="[row.latitud, row.longitud]"
                :icon="iconFor(row)"
                @click="selectRecinto(row)"
              >
                <l-popup>
                  <div style="min-width: 240px">
                    <div class="text-weight-bold">{{ row.nombre }}</div>
                    <div class="q-mt-xs">Mesas: {{ row.mesas_count }}</div>
                    <div class="q-mt-xs">Jefe: {{ row.jefesText }}</div>
                    <q-btn
                      class="q-mt-sm"
                      color="primary"
                      icon="directions"
                      label="Ir ahí"
                      no-caps
                      dense
                      @click="goToRecinto(row)"
                    />
                  </div>
                </l-popup>
              </l-marker>

              <l-circle-marker
                v-if="myLocation"
                :lat-lng="[myLocation.lat, myLocation.lng]"
                :radius="8"
                :color="'#b91c1c'"
                :fill-color="'#ef4444'"
                :fill-opacity="0.95"
              >
                <l-popup>
                  <div class="text-weight-bold">Mi ubicación</div>
                </l-popup>
              </l-circle-marker>
            </l-map>
          </q-card-section>
        </q-card>
      </div>

      <div v-if="!expandedMap" class="col-12 col-lg-4">
        <q-card flat bordered>
          <q-card-section class="row items-center">
            <div class="text-h6 text-weight-bold">Listado de Recintos</div>
            <q-space />
            <q-chip outline color="primary">{{ rows.length }}</q-chip>
          </q-card-section>
          <q-separator />

          <q-card-section class="q-pa-sm" style="max-height: 72vh; overflow: auto;">
            <q-inner-loading :showing="loading">
              <q-spinner color="primary" size="36px" />
            </q-inner-loading>

            <q-card
              v-if="selectedRecinto"
              flat
              bordered
              class="q-mb-sm bg-blue-1"
            >
              <q-card-section class="q-pa-sm">
                <div class="text-weight-bold">{{ selectedRecinto.nombre }}</div>
                <div class="text-caption text-grey-8">Jefe: {{ selectedRecinto.jefesText }}</div>
                <div class="text-caption text-grey-8">Mesas: {{ selectedRecinto.mesas_count }}</div>
                <q-btn
                  class="q-mt-sm"
                  color="primary"
                  icon="directions"
                  label="Ir ahí"
                  no-caps
                  dense
                  @click="goToRecinto(selectedRecinto)"
                />
              </q-card-section>
            </q-card>

            <div v-if="!loading && !rows.length" class="text-grey-7 q-pa-md text-center">
              No hay recintos públicos disponibles.
            </div>

            <q-list v-else separator>
              <q-item
                v-for="row in rows"
                :key="row.id"
                clickable
                v-ripple
                :active="selectedRecinto?.id === row.id"
                active-class="recinto-active"
                @click="selectRecinto(row)"
              >
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ row.nombre }}</q-item-label>
                  <q-item-label caption>Jefe: {{ row.jefesText }}</q-item-label>
                  <q-item-label caption>Mesas: {{ row.mesas_count }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
import { LCircleMarker, LControlLayers, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import { nextTick } from 'vue'

export default {
  name: 'PublicRecintosPage',
  components: { LCircleMarker, LControlLayers, LMap, LMarker, LPopup, LTileLayer },
  data () {
    return {
      loading: false,
      locating: false,
      expandedMap: false,
      rows: [],
      selectedRecinto: null,
      selectedRecintoId: null,
      recintoOptionsFiltered: [],
      myLocation: null,
      center: [-17.9667, -67.1167],
      zoom: 13
    }
  },
  computed: {
    mapColumnClass () {
      return this.expandedMap ? 'col-12' : 'col-12 col-lg-8'
    },
    mapSectionStyle () {
      return this.expandedMap
        ? 'height: calc(100vh - 160px); min-height: 520px;'
        : 'height: 72vh; min-height: 420px;'
    },
    recintoOptions () {
      return this.rows.map(row => ({
        label: row.nombre,
        value: row.id
      }))
    }
  },
  mounted () {
    this.load()
  },
  methods: {
    async load () {
      this.loading = true
      try {
        const data = await this.$axios.get('public/recintos-mapa').then(r => r.data)
        this.rows = (Array.isArray(data?.data) ? data.data : [])
          .filter(row => Number.isFinite(Number(row.latitud)) && Number.isFinite(Number(row.longitud)))
          .map(row => ({
            ...row,
            latitud: Number(row.latitud),
            longitud: Number(row.longitud),
            mesas_count: Number(row.mesas_count || 0),
            jefesText: (row.jefes || []).map(j => j.name).join(' | ') || 'Sin jefe asignado'
          }))

        if (this.rows.length) {
          this.selectedRecinto = this.rows[0]
          this.selectedRecintoId = this.rows[0].id
          this.recintoOptionsFiltered = this.recintoOptions
          this.center = [this.rows[0].latitud, this.rows[0].longitud]
          this.$nextTick(() => this.fitRows())
        } else {
          this.recintoOptionsFiltered = []
        }
      } catch (e) {
        this.$q.notify({
          color: 'negative',
          message: e.response?.data?.message || 'No se pudo cargar los recintos públicos'
        })
      } finally {
        this.loading = false
      }
    },
    fitRows () {
      const leaflet = this.$refs.mapRef?.leafletObject
      if (!leaflet || !this.rows.length) return
      const bounds = L.latLngBounds(this.rows.map(row => [row.latitud, row.longitud]))
      if (bounds.isValid()) {
        leaflet.fitBounds(bounds, { padding: [24, 24] })
      }
    },
    selectRecinto (row) {
      this.selectedRecinto = row
      this.selectedRecintoId = row.id
      const leaflet = this.$refs.mapRef?.leafletObject
      if (!leaflet) return
      leaflet.flyTo([row.latitud, row.longitud], Math.max(this.zoom, 16))
    },
    onSelectRecinto (recintoId) {
      const row = this.rows.find(item => item.id === recintoId)
      if (!row) return
      this.selectRecinto(row)
    },
    filterRecintos (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.recintoOptionsFiltered = this.recintoOptions
          return
        }
        this.recintoOptionsFiltered = this.recintoOptions.filter(option =>
          String(option.label || '').toLowerCase().includes(needle)
        )
      })
    },
    locateMe () {
      if (!navigator.geolocation) {
        this.$q.notify({
          color: 'negative',
          message: 'Tu navegador no soporta geolocalización'
        })
        return
      }

      this.locating = true
      navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
          this.myLocation = {
            lat: Number(coords.latitude),
            lng: Number(coords.longitude)
          }
          const leaflet = this.$refs.mapRef?.leafletObject
          if (leaflet) {
            leaflet.flyTo([this.myLocation.lat, this.myLocation.lng], 16)
          }
          this.locating = false
        },
        () => {
          this.locating = false
          this.$q.notify({
            color: 'negative',
            message: 'No se pudo obtener tu ubicación'
          })
        },
        { enableHighAccuracy: true, timeout: 10000 }
      )
    },
    goToRecinto (row) {
      if (!row) return
      const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${row.latitud},${row.longitud}`)}`
      window.open(url, '_blank', 'noopener,noreferrer')
    },
    iconFor (row) {
      const hasJefe = Array.isArray(row.jefes) && row.jefes.length > 0
      const color = hasJefe ? '#16a34a' : '#f59e0b'

      return L.divIcon({
        className: '',
        html: `<div style="
          width:14px;
          height:14px;
          border-radius:50%;
          background:${color};
          border:2px solid #ffffff;
          box-shadow:0 1px 4px rgba(15, 23, 42, 0.35);
        "></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7]
      })
    },
    async syncMapSize () {
      await nextTick()
      const leaflet = this.$refs.mapRef?.leafletObject
      if (!leaflet) return
      leaflet.invalidateSize()
      if (this.selectedRecinto) {
        leaflet.flyTo([this.selectedRecinto.latitud, this.selectedRecinto.longitud], Math.max(this.zoom, 16))
        return
      }
      this.fitRows()
    }
  },
  watch: {
    expandedMap () {
      this.syncMapSize()
    }
  }
}
</script>

<style scoped>
.recinto-active {
  background: #e0f2fe;
}

.public-map-expanded {
  position: relative;
  z-index: 5;
}

.public-map-shell {
  position: relative;
}

.public-map-toolbar {
  position: absolute;
  top: 12px;
  left: 56px;
  right: 70px;
  z-index: 1000;
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.public-map-select {
  min-width: 260px;
  max-width: 420px;
  background: rgba(255, 255, 255, 0.96);
}
</style>
