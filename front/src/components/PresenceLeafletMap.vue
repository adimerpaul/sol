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

      <l-marker v-if="hasRecintoLatLng" :lat-lng="[recintoLat, recintoLng]">
        <l-icon :icon-anchor="[12, 24]" :popup-anchor="[0, -20]" class-name="recinto-marker-container">
          <div class="recinto-marker"></div>
        </l-icon>
        <l-popup>
          <div>
            Recinto<br>
            Lat: {{ recintoLat.toFixed(7) }}<br>
            Lng: {{ recintoLng.toFixed(7) }}
          </div>
        </l-popup>
      </l-marker>
    </l-map>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { LControlLayers, LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet'
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

const lat = computed(() => Number(props.latitud))
const lng = computed(() => Number(props.longitud))
const hasLatLng = computed(() => Number.isFinite(lat.value) && Number.isFinite(lng.value))
const recintoLat = computed(() => Number(props.recintoLatitud))
const recintoLng = computed(() => Number(props.recintoLongitud))
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

.recinto-marker-container {
  background: transparent !important;
  border: none !important;
}

.recinto-marker {
  width: 18px;
  height: 18px;
  border-radius: 4px;
  background: #1e3a8a;
  border: 2px solid #ffffff;
  box-shadow: 0 0 10px rgba(30, 58, 138, 0.5);
}
</style>
