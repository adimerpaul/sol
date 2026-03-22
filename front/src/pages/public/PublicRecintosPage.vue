<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md">
      <div class="col-12 col-lg-8">
        <q-card flat bordered class="overflow-hidden">
          <q-card-section class="row items-center">
            <div>
              <div class="text-h6 text-weight-bold">Mapa de Recintos</div>
              <div class="text-caption text-grey-7">{{ rows.length }} recintos públicos</div>
            </div>
            <q-space />
            <q-btn
              color="primary"
              icon="my_location"
              label="Dónde estoy"
              no-caps
              :loading="locating"
              @click="locateMe"
            />
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-none" style="height: 72vh; min-height: 420px;">
            <l-map
              ref="mapRef"
              :zoom="zoom"
              :center="center"
              :use-global-leaflet="false"
              :options="{ attributionControl: false }"
              style="height: 100%; width: 100%;"
            >
              <l-tile-layer
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                layer-type="base"
                name="OpenStreetMap"
              />

              <l-marker
                v-for="row in rows"
                :key="row.id"
                :lat-lng="[row.latitud, row.longitud]"
                @click="selectRecinto(row)"
              >
                <l-popup>
                  <div class="text-weight-bold">{{ row.nombre }}</div>
                  <div>Mesas: {{ row.mesas_count }}</div>
                  <div>Jefe: {{ row.jefesText }}</div>
                  <q-btn
                    class="q-mt-sm"
                    color="primary"
                    icon="directions"
                    label="Ir ahí"
                    no-caps
                    dense
                    @click="goToRecinto(row)"
                  />
                </l-popup>
              </l-marker>

              <l-circle-marker
                v-if="myLocation"
                :lat-lng="[myLocation.lat, myLocation.lng]"
                :radius="10"
                :color="'#dc2626'"
                :fill-color="'#ef4444'"
                :fill-opacity="0.9"
              >
                <l-popup>
                  <div class="text-weight-bold">Mi ubicación</div>
                </l-popup>
              </l-circle-marker>
            </l-map>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-4">
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
import { LCircleMarker, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

export default {
  name: 'PublicRecintosPage',
  components: { LCircleMarker, LMap, LMarker, LPopup, LTileLayer },
  data () {
    return {
      loading: false,
      locating: false,
      rows: [],
      selectedRecinto: null,
      myLocation: null,
      center: [-17.9667, -67.1167],
      zoom: 13
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
            jefesText: (row.jefes || []).map(j => j.name).join(' | ') || 'Sin jefe asignado'
          }))

        if (this.rows.length) {
          this.selectedRecinto = this.rows[0]
          this.center = [this.rows[0].latitud, this.rows[0].longitud]
          this.$nextTick(() => this.fitRows())
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
      const bounds = this.rows.map(row => [row.latitud, row.longitud])
      leaflet.fitBounds(bounds, { padding: [24, 24] })
    },
    selectRecinto (row) {
      this.selectedRecinto = row
      const leaflet = this.$refs.mapRef?.leafletObject
      if (!leaflet) return
      leaflet.flyTo([row.latitud, row.longitud], Math.max(this.zoom, 16))
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
    }
  }
}
</script>

<style scoped>
.recinto-active {
  background: #e0f2fe;
}
</style>
