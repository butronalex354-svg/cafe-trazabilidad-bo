<template>
  <div v-if="coords" class="map-view-wrap" :class="{ 'map-view-wrap--fill': fill }">
    <div class="map-view-col">
      <div ref="mapEl" class="map-view-el" :style="{ height: height + 'px' }"></div>
      <div v-if="leyenda" class="map-view-leyenda">
        <span class="muestra" :class="tieneBorde ? 'muestra--borde' : 'muestra--aprox'"></span>
        {{ leyenda }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import { bordeValido, areaHectareas, centro, formatoHa } from '@/utils/geo'

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
  fill: { type: Boolean, default: false }, // ocupar todo el ancho de su columna, en vez de un recuadro centrado chico
  poligono: { type: Array, default: null }, // borde dibujado de la parcela: [[lat, lng], ...]
  hectareas: { type: [Number, String], default: null } // tamaño registrado, para el area aproximada si no hay borde
})

const mapEl = ref(null)
let map = null
let resizeObserver = null

const tieneBorde = computed(() => bordeValido(props.poligono))

const coords = computed(() => {
  if (props.ubicacion) {
    const partes = props.ubicacion.split(',').map(p => parseFloat(p.trim()))
    if (partes.length === 2 && !Number.isNaN(partes[0]) && !Number.isNaN(partes[1])) {
      return { lat: partes[0], lng: partes[1] }
    }
  }
  return tieneBorde.value ? centro(props.poligono) : null
})

const hectareasRegistradas = computed(() => {
  const ha = Number(props.hectareas)
  return Number.isFinite(ha) && ha > 0 ? ha : null
})

const leyenda = computed(() => {
  if (tieneBorde.value) return `Área de la parcela marcada en el mapa (${formatoHa(areaHectareas(props.poligono))} ha)`
  if (hectareasRegistradas.value) return `Área aproximada según el tamaño registrado (${formatoHa(hectareasRegistradas.value)} ha)`
  return ''
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
    .bindPopup(`<b>${props.etiqueta || 'Parcela'}</b><br>${props.ubicacion || ''}`)

  // Area de la parcela: el borde dibujado por el productor (linea continua)
  // o, si no lo dibujo, un circulo aproximado con las hectareas registradas
  // (linea punteada), para que se entienda el tamaño real del terreno.
  let zona = null
  if (tieneBorde.value) {
    zona = L.polygon(props.poligono, { color: '#8b4513', weight: 3, fillColor: '#e0a84a', fillOpacity: 0.3 })
  } else if (hectareasRegistradas.value) {
    const radio = Math.sqrt(hectareasRegistradas.value * 10000 / Math.PI)
    zona = L.circle([coords.value.lat, coords.value.lng], { radius: radio, color: '#8b4513', weight: 2, dashArray: '6 6', fillColor: '#e0a84a', fillOpacity: 0.15 })
  }
  if (zona) {
    zona.addTo(map).bindTooltip(leyenda.value, { sticky: true })
    limitesZona = zona.getBounds()
    encuadrar()
  }

  // El bug clasico de Leaflet dentro de un panel con transicion (q-expansion-item):
  // el mapa nace midiendo su contenedor ANTES de que termine de abrirse, y se
  // queda pegado en una esquina. Un solo setTimeout no siempre alcanza, asi que
  // se observa el tamaño real del contenedor y se corrige cada vez que cambia.
  resizeObserver = new ResizeObserver(redibujar)
  resizeObserver.observe(mapEl.value)
  // Desde que la persona toca, arrastra o acerca el mapa, ya no se re-encuadra solo
  for (const ev of ['mousedown', 'touchstart', 'wheel']) mapEl.value.addEventListener(ev, marcarInteraccion, { passive: true })

  // Ademas: si la pagina tiene una animacion de entrada (transform) al
  // cargar, Leaflet puede dibujar mal algunos "tiles" de las esquinas
  // aunque el contenedor no haya cambiado de tamaño (el ResizeObserver no
  // detecta eso). Se refuerza con un par de redibujados forzados al toque.
  setTimeout(redibujar, 300)
  setTimeout(redibujar, 800)
})

// Encuadra el area de la parcela. Si el mapa se creo dentro de un panel que
// todavia se estaba abriendo (tamaño 0), el primer encuadre sale mal; por eso
// se repite cada vez que el contenedor cambia de tamaño, hasta que el usuario
// mueve o acerca el mapa por su cuenta.
let limitesZona = null
let usuarioMovio = false
function encuadrar () {
  if (map && limitesZona && !usuarioMovio && mapEl.value?.clientWidth > 0) {
    map.fitBounds(limitesZona, { padding: [24, 24], maxZoom: 18, animate: false })
  }
}
function redibujar () {
  if (!map) return
  map.invalidateSize()
  encuadrar()
}
function marcarInteraccion () { usuarioMovio = true }

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
.map-view-col {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.map-view-wrap--fill .map-view-col {
  align-items: stretch;
}
.map-view-leyenda {
  margin-top: 6px;
  font-size: 12px;
  color: #6d4c41;
  display: flex;
  align-items: center;
  gap: 6px;
}
.muestra {
  display: inline-block;
  width: 18px;
  height: 10px;
  background: rgba(224, 168, 74, 0.35);
  border: 2px solid #8b4513;
  border-radius: 2px;
}
.muestra--aprox {
  border-style: dashed;
}
</style>
