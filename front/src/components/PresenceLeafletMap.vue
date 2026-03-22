<template>
  <div class="presence-map-wrapper">
    <l-map
      style="height: 260px"
      v-model:zoom="zoom"
      :center="mapCenter"
      :use-global-leaflet="false"
      :options="{ attributionControl: false, zoomControl: true }"
      ref="mapRef"
    >
      <l-control-layers position="topright" />

      <l-tile-layer
        layer-type="base"
        name="OpenStreetMap"
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        attribution="&copy; OpenStreetMap contributors"
        :subdomains="['a', 'b', 'c']"
        :max-zoom="19"
        :visible="false"
      />

      <l-tile-layer
        layer-type="base"
        name="Google Calle"
        url="https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
        attribution="Map data &copy; Google"
        :max-zoom="21"
        :visible="true"
      />

      <l-tile-layer
        layer-type="base"
        name="Google Satélite"
        url="https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}"
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

      <l-marker v-if="hasLatLng" :lat-lng="[lat, lng]">
        <l-popup>
          <div>
            Delegado<br>
            Lat: {{ lat.toFixed(7) }}<br>
            Lng: {{ lng.toFixed(7) }}
          </div>
        </l-popup>
      </l-marker>

      <l-circle-marker
        v-if="hasRecintoLatLng"
        :lat-lng="[recintoLat, recintoLng]"
        :radius="10"
        color="#1d4ed8"
        :weight="3"
        fill-color="#60a5fa"
        :fill-opacity="0.92"
      >
        <l-popup>
          <div>
            Recinto<br>
            Lat: {{ recintoLat.toFixed(7) }}<br>
            Lng: {{ recintoLng.toFixed(7) }}
          </div>
        </l-popup>
      </l-circle-marker>
    </l-map>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { LCircleMarker, LControlLayers, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

const props = defineProps({
  latitud: { type: [Number, String], default: null },
  longitud: { type: [Number, String], default: null },
  recintoLatitud: { type: [Number, String], default: null },
  recintoLongitud: { type: [Number, String], default: null },
  zoomInit: { type: Number, default: 16 },
  fallbackCenter: { type: Array, default: () => [-17.9647, -67.1060] }
})

const mapRef = ref(null)
const zoom = ref(props.zoomInit)

function normalizeCoords(rawLat, rawLng) {
  let lat = Number(rawLat)
  let lng = Number(rawLng)

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return { lat, lng }
  }

  // En este proyecto las coordenadas válidas están en Bolivia.
  // Algunos registros llegan invertidos (lat=-67, lng=-17); aquí se corrigen.
  const looksSwapped =
    lat <= -40 && lat >= -90 &&
    lng <= 0 && lng >= -35

  if (looksSwapped) {
    return { lat: lng, lng: lat }
  }

  return { lat, lng }
}

const normalizedDelegado = computed(() => normalizeCoords(props.latitud, props.longitud))
const lat = computed(() => normalizedDelegado.value.lat)
const lng = computed(() => normalizedDelegado.value.lng)
const hasLatLng = computed(() => Number.isFinite(lat.value) && Number.isFinite(lng.value))
const normalizedRecinto = computed(() => normalizeCoords(props.recintoLatitud, props.recintoLongitud))
const recintoLat = computed(() => normalizedRecinto.value.lat)
const recintoLng = computed(() => normalizedRecinto.value.lng)
const hasRecintoLatLng = computed(() => Number.isFinite(recintoLat.value) && Number.isFinite(recintoLng.value))
const mapCenter = computed(() => {
  if (hasLatLng.value) return [lat.value, lng.value]
  if (hasRecintoLatLng.value) return [recintoLat.value, recintoLng.value]
  return props.fallbackCenter
})

watch(
  () => [props.latitud, props.longitud, props.recintoLatitud, props.recintoLongitud],
  async () => {
    await nextTick()
    const leaflet = mapRef.value?.leafletObject
    if (!leaflet) return
    if (hasLatLng.value && hasRecintoLatLng.value) {
      leaflet.fitBounds([[lat.value, lng.value], [recintoLat.value, recintoLng.value]], { padding: [24, 24] })
      return
    }
    if (hasLatLng.value) {
      leaflet.flyTo([lat.value, lng.value], Math.max(zoom.value, props.zoomInit))
      return
    }
    if (hasRecintoLatLng.value) {
      leaflet.flyTo([recintoLat.value, recintoLng.value], Math.max(zoom.value, props.zoomInit))
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.presence-map-wrapper {
  width: 100%;
}
</style>
