<template>
  <div class="map-wrapper">
    <l-map
      style="height: 520px"
      v-model:zoom="zoom"
      :center="centerComputed"
      :use-global-leaflet="false"
      :options="{ attributionControl: false }"
      ref="mapRef"
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

      <!-- ✅ markers de recintos -->
      <l-marker
        v-for="r in validMarkers"
        :key="r.id"
        :lat-lng="[Number(r.latitud), Number(r.longitud)]"
        :icon="iconFor(r)"
        @click="selectRecinto(r)"
      >
        <l-popup>
          <div style="min-width: 220px">
            <div class="text-weight-bold">{{ r.nombre }}</div>
            <div class="text-caption">
              Lat: {{ r.latitud }}<br />
              Lng: {{ r.longitud }}
            </div>
            <div class="q-mt-xs">
              <span v-if="r.jefe?.length" class="text-positive">
                Jefe: {{ r.jefe[0].name }} ({{ r.jefe[0].username }})
              </span>
              <span v-else class="text-negative">Sin jefe asignado</span>
            </div>
          </div>
        </l-popup>
      </l-marker>
    </l-map>

    <div class="text-caption text-grey-7 q-mt-sm">
      Tip: haz click en un marcador para seleccionar el recinto.
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue'
import { LMap, LTileLayer, LMarker, LPopup, LControlLayers } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl   from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

const props = defineProps({
  markers: { type: Array, default: () => [] },
  center: { type: Array, default: () => [-17.9647, -67.1060] },
  zoomInit: { type: Number, default: 13 }
})
const emit = defineEmits(['select'])

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

const mapRef = ref(null)
const zoom = ref(props.zoomInit)

const validMarkers = computed(() => {
  return (props.markers || []).filter(r =>
    r && r.latitud != null && r.longitud != null &&
    Number.isFinite(Number(r.latitud)) &&
    Number.isFinite(Number(r.longitud))
  )
})

const centerComputed = computed(() => {
  // si hay markers, centra en el primero; si no, usa center default
  if (validMarkers.value.length) {
    return [Number(validMarkers.value[0].latitud), Number(validMarkers.value[0].longitud)]
  }
  return props.center
})

function selectRecinto (r) {
  emit('select', r)
}

function iconFor (r) {
  // verde si tiene jefe, rojo si no
  const has = Array.isArray(r.jefe) && r.jefe.length > 0
  const color = has ? 'green' : 'red'

  // icono simple con color (sin librerías extra)
  return L.divIcon({
    className: '',
    html: `<div style="
      width:14px;height:14px;border-radius:50%;
      background:${color};
      border:2px solid white;
      box-shadow:0 1px 4px rgba(0,0,0,.35);
    "></div>`,
    iconSize: [14, 14],
    iconAnchor: [7, 7]
  })
}

async function fitBounds () {
  const leaflet = mapRef.value?.leafletObject
  if (!leaflet || !validMarkers.value.length) return

  const bounds = L.latLngBounds(validMarkers.value.map(r => [Number(r.latitud), Number(r.longitud)]))
  await nextTick()
  leaflet.fitBounds(bounds, { padding: [24, 24] })
}

watch(() => props.markers, () => fitBounds(), { deep: true })
</script>

<style scoped>
.map-wrapper { width: 100%; }
</style>
