<template>
  <div class="map-wrapper">
    <q-btn
      class="map-expand-btn"
      icon="open_in_full"
      round
      dense
      unelevated
      color="primary"
      text-color="white"
      @click="openExpandedMap"
    >
      <q-tooltip>Ver mapa grande</q-tooltip>
    </q-btn>

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

      <l-marker
        v-for="r in visibleMarkers"
        :key="r.id"
        :lat-lng="[r._lat, r._lng]"
        :icon="iconFor(r)"
        @click="selectRecinto(r)"
      >
        <l-popup>
          <div style="min-width: 240px">
            <div class="text-weight-bold">{{ r.nombre }}</div>

            <div class="text-caption q-mt-xs">
              Lat: {{ r._lat }}<br />
              Lng: {{ r._lng }}
            </div>

            <div class="q-mt-xs">
              <span v-if="r.jefe?.length" class="text-positive">
                Jefes:
                {{ r.jefe.map(j => `${j.name} (${j.celular || j.username})`).join(', ') }}
              </span>
              <span v-else class="text-negative">Sin jefe asignado</span>
            </div>
            <div class="q-mt-xs">
              Mesas: {{ r.mesas_total || 0 }} · Asignadas: {{ r.mesas_asignadas || 0 }}
            </div>
          </div>
        </l-popup>
      </l-marker>
    </l-map>

    <div class="text-caption text-grey-7 q-mt-sm">
      Tip: haz click en un marcador para seleccionar el recinto.
    </div>

    <q-dialog v-model="expandedMap" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="map-dialog-card">
        <q-btn
          class="map-dialog-close-btn"
          icon="close"
          round
          size="md"
          unelevated
          color="negative"
          text-color="white"
          @click="expandedMap = false"
        >
          <q-tooltip>Cerrar mapa</q-tooltip>
        </q-btn>

        <q-card-section class="q-pa-none map-dialog-section">
          <l-map
            class="map-dialog-canvas"
            v-model:zoom="dialogZoom"
            :center="dialogCenter"
            :use-global-leaflet="false"
            :options="{ attributionControl: false }"
            ref="dialogMapRef"
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
              v-for="r in visibleMarkers"
              :key="`dialog-${r.id}`"
              :lat-lng="[r._lat, r._lng]"
              :icon="iconFor(r)"
              @click="selectRecinto(r)"
            >
              <l-popup>
                <div style="min-width: 240px">
                  <div class="text-weight-bold">{{ r.nombre }}</div>

                  <div class="text-caption q-mt-xs">
                    Lat: {{ r._lat }}<br />
                    Lng: {{ r._lng }}
                  </div>

                  <div class="q-mt-xs">
                    <span v-if="r.jefe?.length" class="text-positive">
                      Jefes:
                      {{ r.jefe.map(j => `${j.name} (${j.celular || j.username})`).join(', ') }}
                    </span>
                    <span v-else class="text-negative">Sin jefe asignado</span>
                  </div>
                  <div class="q-mt-xs">
                    Mesas: {{ r.mesas_total || 0 }} · Asignadas: {{ r.mesas_asignadas || 0 }}
                  </div>
                </div>
              </l-popup>
            </l-marker>
          </l-map>
        </q-card-section>
      </q-card>
    </q-dialog>
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
  zoomInit: { type: Number, default: 13 },

  // ✅ NUEVO: cuando el padre quiera enfocar un recinto
  focusRecinto: { type: Object, default: null }
})
const emit = defineEmits(['select'])

L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2xUrl,
  iconUrl: markerIconUrl,
  shadowUrl: markerShadowUrl
})

const mapRef = ref(null)
const dialogMapRef = ref(null)
const zoom = ref(props.zoomInit)
const dialogZoom = ref(props.zoomInit)
const expandedMap = ref(false)

function toNum (v) {
  const n = parseFloat(String(v ?? '').trim())
  return Number.isFinite(n) ? n : null
}

function inRangeLatLng (lat, lng) {
  return lat !== null && lng !== null &&
    lat >= -90 && lat <= 90 &&
    lng >= -180 && lng <= 180
}

function sanitizeMarkers (list) {
  return (list || [])
    .map(r => {
      const lat = toNum(r?.latitud)
      const lng = toNum(r?.longitud)
      return { ...r, _lat: lat, _lng: lng }
    })
    .filter(r => inRangeLatLng(r._lat, r._lng) && !(r._lat === 0 && r._lng === 0))
}

const cleanMarkers = computed(() => sanitizeMarkers(props.markers))

const visibleMarkers = computed(() => cleanMarkers.value || [])

const centerComputed = computed(() => {
  if (visibleMarkers.value.length) {
    return [visibleMarkers.value[0]._lat, visibleMarkers.value[0]._lng]
  }
  return props.center
})

const dialogCenter = computed(() => {
  const leaflet = mapRef.value?.leafletObject
  if (leaflet) {
    const center = leaflet.getCenter()
    if (center) {
      return [center.lat, center.lng]
    }
  }

  return centerComputed.value
})

function selectRecinto (r) {
  emit('select', r)
}

function iconFor (r) {
  const has = Array.isArray(r.jefe) && r.jefe.length > 0
  const total = Number(r.mesas_total || 0)
  const asignadas = Number(r.mesas_asignadas || 0)
  const delegadosOk = total === 0 ? true : asignadas >= total
  const color = has && delegadosOk ? 'green' : (has ? 'orange' : 'red')

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

async function fitBoundsFrom (list) {
  const leaflet = mapRef.value?.leafletObject
  if (!leaflet) return

  const clean = sanitizeMarkers(list)
  if (!clean.length) return

  await nextTick()

  try {
    if (clean.length === 1) {
      leaflet.flyTo([clean[0]._lat, clean[0]._lng], Math.max(zoom.value, 16))
      return
    }

    const bounds = L.latLngBounds(clean.map(r => [r._lat, r._lng]))
    if (!bounds || !bounds.isValid || !bounds.isValid()) return

    leaflet.fitBounds(bounds, { padding: [24, 24] })
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn('fitBoundsFrom() ignorado:', e)
  }
}

function openExpandedMap () {
  const leaflet = mapRef.value?.leafletObject
  if (leaflet) {
    dialogZoom.value = leaflet.getZoom()
  }

  expandedMap.value = true
}

/** ✅ cuando llegan markers -> encuadrar */
watch(
  () => props.markers,
  (newVal) => fitBoundsFrom(newVal),
  { deep: true, immediate: true }
)

/** ✅ NUEVO: enfocar 1 recinto (desde el combo) */
watch(
  () => props.focusRecinto,
  async (fr) => {
    const leaflet = mapRef.value?.leafletObject
    if (!leaflet || !fr) return

    const lat = toNum(fr.latitud)
    const lng = toNum(fr.longitud)
    if (!inRangeLatLng(lat, lng)) return

    await nextTick()
    leaflet.flyTo([lat, lng], Math.max(zoom.value, 17))
  },
  { deep: true }
)

watch(expandedMap, async (isOpen) => {
  if (!isOpen) return

  await nextTick()

  const sourceMap = mapRef.value?.leafletObject
  const targetMap = dialogMapRef.value?.leafletObject
  if (!targetMap) return

  targetMap.invalidateSize()

  if (!sourceMap) return

  const center = sourceMap.getCenter()
  dialogZoom.value = sourceMap.getZoom()

  if (center) {
    targetMap.setView([center.lat, center.lng], dialogZoom.value, { animate: false })
  }
})
</script>

<style scoped>
.map-wrapper {
  position: relative;
  width: 100%;
}

.map-canvas {
  height: 520px;
}

.map-expand-btn {
  position: absolute;
  top: 12px;
  right: 56px;
  z-index: 1200;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.28);
  border: 2px solid rgba(255, 255, 255, 0.9);
}

.map-dialog-card {
  position: relative;
  background: #fff;
}

.map-dialog-section {
  height: 100vh;
}

.map-dialog-canvas {
  height: 100%;
}

.map-dialog-close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 1200;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.28);
  border: 2px solid rgba(255, 255, 255, 0.9);
}
</style>
