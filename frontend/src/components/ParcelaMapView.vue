<template>
  <div v-if="coords" class="map-view-wrap" :class="{ 'map-view-wrap--fill': fill }">
    <div ref="mapEl" class="map-view-el" :style="{ height: height + 'px' }"></div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow
})

const props = defineProps({
  ubicacion: { type: String, default: '' },
  etiqueta: { type: String, default: '' },
  height: { type: Number, default: 140 },
  fill: { type: Boolean, default: false } // ocupar todo el ancho de su columna, en vez de un recuadro centrado chico
})

const mapEl = ref(null)
let map = null
let resizeObserver = null

const coords = computed(() => {
  if (!props.ubicacion) return null
  const partes = props.ubicacion.split(',').map(p => parseFloat(p.trim()))
  if (partes.length === 2 && !Number.isNaN(partes[0]) && !Number.isNaN(partes[1])) {
    return { lat: partes[0], lng: partes[1] }
  }
  return null
})

onMounted(() => {
  if (!coords.value) return

  // Mapa de solo vista: se puede mover/zoomear con los botones, pero no
  // dispara nada al hacer clic (esa interacción es solo del selector de edición).
  map = L.map(mapEl.value, { scrollWheelZoom: false }).setView([coords.value.lat, coords.value.lng], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map)

  L.marker([coords.value.lat, coords.value.lng])
    .addTo(map)
    .bindPopup(`<b>${props.etiqueta || 'Parcela'}</b><br>${props.ubicacion}`)

  // El bug clasico de Leaflet dentro de un panel con transicion (q-expansion-item):
  // el mapa nace midiendo su contenedor ANTES de que termine de abrirse, y se
  // queda pegado en una esquina. Un solo setTimeout no siempre alcanza, asi que
  // se observa el tamaño real del contenedor y se corrige cada vez que cambia.
  resizeObserver = new ResizeObserver(() => map && map.invalidateSize())
  resizeObserver.observe(mapEl.value)
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
  if (map) map.remove()
})
</script>

<style scoped>
.map-view-wrap {
  padding: 0 16px 16px;
  display: flex;
  justify-content: center;
}
.map-view-wrap--fill {
  padding: 0;
  justify-content: stretch;
}
.map-view-el {
  width: 100%;
  max-width: 340px;
  border-radius: 20px;
  border: 3px solid rgba(224, 168, 74, 0.4);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  overflow: hidden;
}
.map-view-wrap--fill .map-view-el {
  max-width: none;
}
</style>
