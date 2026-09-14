<template>
  <div>
    <div ref="mapEl" class="map-picker"></div>
    <div class="text-caption text-grey-7 q-mt-xs">
      Haz clic en el mapa para marcar la ubicación exacta de la parcela.
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

// El icono por defecto de Leaflet no carga bien con bundlers tipo Vite,
// hay que reasignarlo manualmente a las imagenes importadas.
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow
})

const props = defineProps({
  modelValue: { type: String, default: '' } // "lat, lng"
})
const emit = defineEmits(['update:modelValue'])

const mapEl = ref(null)
let map = null
let marker = null

function parseCoords (texto) {
  if (!texto) return null
  const partes = texto.split(',').map(p => parseFloat(p.trim()))
  if (partes.length === 2 && !Number.isNaN(partes[0]) && !Number.isNaN(partes[1])) {
    return { lat: partes[0], lng: partes[1] }
  }
  return null
}

function ubicarMarcador (lat, lng) {
  if (marker) {
    marker.setLatLng([lat, lng])
  } else {
    marker = L.marker([lat, lng]).addTo(map)
  }
  emit('update:modelValue', `${lat.toFixed(6)}, ${lng.toFixed(6)}`)
}

onMounted(() => {
  const coords = parseCoords(props.modelValue) || { lat: -17.3895, lng: -66.1568 } // Cochabamba, centro por defecto

  map = L.map(mapEl.value).setView([coords.lat, coords.lng], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map)

  if (parseCoords(props.modelValue)) {
    marker = L.marker([coords.lat, coords.lng]).addTo(map)
  }

  map.on('click', (e) => {
    ubicarMarcador(e.latlng.lat, e.latlng.lng)
  })

  // el mapa a veces nace con tamaño incorrecto dentro de un q-dialog
  setTimeout(() => map.invalidateSize(), 200)
})

watch(() => props.modelValue, (nuevo) => {
  const coords = parseCoords(nuevo)
  if (coords && map && marker) {
    marker.setLatLng([coords.lat, coords.lng])
  }
})

onBeforeUnmount(() => {
  if (map) map.remove()
})
</script>

<style scoped>
.map-picker {
  height: 220px;
  width: 100%;
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.15);
}
</style>
