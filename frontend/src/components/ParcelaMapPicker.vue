<template>
  <div>
    <q-btn-toggle
      v-model="modo"
      spread
      no-caps
      dense
      unelevated
      toggle-color="brown-7"
      color="white"
      text-color="brown-8"
      class="q-mb-xs modo-toggle"
      :options="[
        { label: 'Marcar ubicación', value: 'punto', icon: 'place' },
        { label: 'Dibujar el borde', value: 'borde', icon: 'pentagon' }
      ]"
    />
    <div ref="mapEl" class="map-picker"></div>
    <div class="text-caption text-grey-7 q-mt-xs">
      <template v-if="modo === 'punto'">Haz clic en el mapa para marcar la ubicación exacta de la parcela.</template>
      <template v-else>Haz clic en cada esquina del terreno, en orden, para dibujar su borde (mínimo 3 puntos).</template>
    </div>
    <div v-if="modo === 'borde' || puntos.length" class="row items-center q-gutter-xs q-mt-xs">
      <span class="text-caption text-brown-8">
        {{ puntos.length }} {{ puntos.length === 1 ? 'punto' : 'puntos' }}
        <template v-if="puntos.length >= 3"> · área marcada: {{ formatoHa(areaHectareas(puntos)) }} ha</template>
      </span>
      <q-space />
      <q-btn flat dense no-caps size="sm" icon="undo" label="Deshacer punto" color="brown-7" :disable="!puntos.length" @click="deshacerPunto" />
      <q-btn flat dense no-caps size="sm" icon="delete_outline" label="Borrar borde" color="negative" :disable="!puntos.length" @click="borrarBorde" />
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
import { areaHectareas, centro, formatoHa } from '@/utils/geo'

// El icono por defecto de Leaflet no carga bien con bundlers tipo Vite,
// hay que reasignarlo manualmente a las imagenes importadas.
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow
})

const props = defineProps({
  modelValue: { type: String, default: '' }, // "lat, lng"
  poligono: { type: Array, default: null } // borde: [[lat, lng], ...]
})
const emit = defineEmits(['update:modelValue', 'update:poligono'])

const mapEl = ref(null)
const modo = ref('punto')
const puntos = ref(Array.isArray(props.poligono) ? props.poligono.map(p => [Number(p[0]), Number(p[1])]) : [])
let map = null
let marker = null
let capaBorde = null

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

// Dibuja el borde: linea mientras hay 1-2 puntos, poligono desde 3.
function dibujarBorde () {
  if (!map) return
  if (capaBorde) { map.removeLayer(capaBorde); capaBorde = null }
  if (!puntos.value.length) return
  capaBorde = L.featureGroup().addTo(map)
  if (puntos.value.length >= 3) {
    L.polygon(puntos.value, { color: '#8b4513', weight: 3, fillColor: '#e0a84a', fillOpacity: 0.3 }).addTo(capaBorde)
  } else {
    L.polyline(puntos.value, { color: '#8b4513', weight: 3, dashArray: '4 4' }).addTo(capaBorde)
  }
  // Cada esquina marcada se ve como un puntito, para saber donde se hizo clic
  puntos.value.forEach(p => L.circleMarker(p, { radius: 4, weight: 2, color: '#8b4513', fillColor: '#fff', fillOpacity: 1, interactive: false }).addTo(capaBorde))
}

function avisarBorde () {
  emit('update:poligono', puntos.value.length >= 3 ? puntos.value.map(p => [Number(p[0].toFixed(6)), Number(p[1].toFixed(6))]) : null)
  // La ubicacion de la parcela pasa a ser el centro del borde dibujado,
  // asi el punto y el area siempre coinciden
  if (puntos.value.length >= 3) {
    const c = centro(puntos.value)
    ubicarMarcador(c.lat, c.lng)
  }
}

function deshacerPunto () {
  puntos.value = puntos.value.slice(0, -1)
  dibujarBorde()
  avisarBorde()
}

function borrarBorde () {
  puntos.value = []
  dibujarBorde()
  avisarBorde()
}

onMounted(() => {
  const coords = parseCoords(props.modelValue) || centro(puntos.value) || { lat: -17.3895, lng: -66.1568 } // Cochabamba, centro por defecto

  map = L.map(mapEl.value).setView([coords.lat, coords.lng], 13)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(map)

  if (parseCoords(props.modelValue)) {
    marker = L.marker([coords.lat, coords.lng]).addTo(map)
  }
  if (puntos.value.length >= 3) {
    dibujarBorde()
    map.fitBounds(capaBorde.getBounds(), { padding: [20, 20], maxZoom: 18 })
  }

  map.on('click', (e) => {
    if (modo.value === 'borde') {
      puntos.value = [...puntos.value, [e.latlng.lat, e.latlng.lng]]
      dibujarBorde()
      avisarBorde()
    } else {
      ubicarMarcador(e.latlng.lat, e.latlng.lng)
    }
  })

  // el mapa a veces nace con tamaño incorrecto dentro de un q-dialog, o se
  // dibujan mal algunas esquinas si la pantalla todavia esta animando su
  // entrada. Se refuerza con varios redibujados forzados al toque.
  setTimeout(() => map && map.invalidateSize(), 200)
  setTimeout(() => map && map.invalidateSize(), 500)
})

watch(() => props.modelValue, (nuevo) => {
  const coords = parseCoords(nuevo)
  if (!coords || !map) return
  if (marker) {
    marker.setLatLng([coords.lat, coords.lng])
  } else {
    marker = L.marker([coords.lat, coords.lng]).addTo(map)
  }
  // Si la ubicacion se escribio a mano (o con "mi ubicacion") y queda fuera
  // de lo que se ve, el mapa se mueve hasta alli para poder dibujar el borde
  if (!map.getBounds().contains([coords.lat, coords.lng])) {
    map.setView([coords.lat, coords.lng], Math.max(map.getZoom(), 16))
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
.modo-toggle {
  border: 1px solid rgba(139, 69, 19, 0.35);
  border-radius: 8px;
}
</style>
