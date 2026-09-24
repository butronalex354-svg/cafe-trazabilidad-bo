<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Monitoreo</div>
    <div class="text-caption text-grey-7 q-mb-md">Clima y observaciones del cultivo por parcela</div>

    <div v-if="produccion.parcelas.length === 0 && !cargandoParcelas" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no tienes parcelas registradas. Ve a <b>Parcelas</b> primero para crear una.
    </div>

    <template v-else>
      <q-select
        v-model="parcelaSeleccionada"
        :options="opcionesParcela"
        emit-value
        map-options
        label="Parcela"
        dense
        outlined
        bg-color="white"
        class="q-mb-md"
        style="max-width: 360px"
      />

      <template v-if="parcelaSeleccionada">
        <div class="text-h6 text-brown-8 q-mb-sm">Clima</div>

        <q-inner-loading :showing="monitoreo.cargandoClima" />

        <div v-if="errorClima" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders q-mb-lg">
          <q-icon name="location_off" size="28px" color="brown-6" class="q-mb-xs" />
          <div>{{ errorClima }}</div>
        </div>

        <div v-else-if="monitoreo.clima" class="q-mb-lg">
          <q-card flat bordered class="clima-actual q-mb-md">
            <q-card-section class="row items-center q-col-gutter-md">
              <div class="col-auto">
                <q-icon :name="iconoClima(monitoreo.clima.actual.probabilidad_lluvia)" size="48px" :color="colorClima(monitoreo.clima.actual.probabilidad_lluvia)" />
              </div>
              <div class="col">
                <div class="text-h4 text-brown-9">{{ Math.round(monitoreo.clima.actual.temperatura) }}°C</div>
                <div class="text-caption text-grey-7">
                  Humedad {{ Math.round(monitoreo.clima.actual.humedad) }}% · Prob. de lluvia {{ Math.round(monitoreo.clima.actual.probabilidad_lluvia) }}%
                </div>
              </div>
            </q-card-section>
          </q-card>

          <div class="text-caption text-grey-7 q-mb-xs">Toca un día para ver el detalle completo</div>
          <div class="fila-pronostico">
            <q-card
              v-for="dia in monitoreo.clima.pronostico"
              :key="dia.fecha"
              flat
              bordered
              clickable
              v-ripple
              class="pronostico-dia"
              :class="claseClima(dia.probabilidad_lluvia)"
              @click="verDetalleDia(dia)"
            >
              <q-card-section class="text-center q-pa-sm">
                <div class="text-caption text-brown-8 text-weight-bold">{{ formatearDiaCorto(dia.fecha) }}</div>
                <q-icon :name="iconoClima(dia.probabilidad_lluvia)" size="22px" :color="colorClima(dia.probabilidad_lluvia)" class="q-my-xs" />
                <div class="text-caption">{{ Math.round(dia.temperatura_max) }}° / {{ Math.round(dia.temperatura_min) }}°</div>
                <div class="text-caption text-grey-7">{{ Math.round(dia.probabilidad_lluvia) }}% lluvia</div>
              </q-card-section>

              <!-- Adelanto rapido al pasar el mouse (solo compu) -->
              <q-tooltip anchor="top middle" self="bottom middle" class="bg-brown-9 text-body2" :offset="[0, 8]">
                Sensación {{ Math.round(dia.sensacion_max) }}° / {{ Math.round(dia.sensacion_min) }}° · Viento {{ Math.round(dia.viento_max) }} km/h
              </q-tooltip>
            </q-card>
          </div>
        </div>

        <div class="row items-center justify-between q-mb-sm">
          <div class="text-h6 text-brown-8">Observaciones del cultivo</div>
          <q-btn unelevated color="brown-8" icon="add_a_photo" label="Nueva observación" @click="abrirNuevaObservacion" />
        </div>

        <q-inner-loading :showing="monitoreo.cargandoObservaciones" />

        <div v-if="!monitoreo.cargandoObservaciones && observacionesDeParcela.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
          Sin observaciones registradas en esta parcela todavía.
        </div>

        <q-list v-else bordered separator class="rounded-borders caja-tema">
          <q-item v-for="obs in observacionesDeParcela" :key="obs.id">
            <q-item-section v-if="obs.foto_url" avatar>
              <q-avatar square size="48px" rounded class="foto-observacion" @click="verFotoGrande(obs.foto_url)">
                <img :src="urlCompleta(obs.foto_url)" />
              </q-avatar>
            </q-item-section>
            <q-item-section v-else avatar>
              <q-icon name="visibility" color="brown-7" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ obs.nota }}</q-item-label>
              <q-item-label caption>{{ formatearFecha(obs.fecha) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row no-wrap">
                <q-btn flat dense round size="sm" icon="edit" color="brown-7" @click="abrirEditarObservacion(obs)" />
                <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarObservacion(obs)" />
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </template>
    </template>

    <!-- Dialogo: detalle de un dia del pronostico -->
    <q-dialog v-model="dlgDetalleDia">
      <q-card v-if="diaSeleccionado" style="min-width: 300px; max-width: 380px" class="caja-tema">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-auto">
            <q-icon :name="iconoClima(diaSeleccionado.probabilidad_lluvia)" size="36px" :color="colorClima(diaSeleccionado.probabilidad_lluvia)" />
          </div>
          <div class="col">
            <div class="text-subtitle1 text-brown-9 text-weight-bold">{{ formatearDiaLargo(diaSeleccionado.fecha) }}</div>
            <div class="text-caption text-grey-7">{{ Math.round(diaSeleccionado.temperatura_max) }}° / {{ Math.round(diaSeleccionado.temperatura_min) }}°</div>
          </div>
        </q-card-section>

        <q-separator inset />

        <q-list dense class="q-py-xs">
          <q-item>
            <q-item-section avatar><q-icon name="thermostat" color="brown-6" /></q-item-section>
            <q-item-section>Sensación térmica</q-item-section>
            <q-item-section side>{{ Math.round(diaSeleccionado.sensacion_max) }}° / {{ Math.round(diaSeleccionado.sensacion_min) }}°</q-item-section>
          </q-item>
          <q-item>
            <q-item-section avatar><q-icon name="water_drop" color="blue-7" /></q-item-section>
            <q-item-section>Probabilidad de lluvia</q-item-section>
            <q-item-section side>{{ Math.round(diaSeleccionado.probabilidad_lluvia) }}%</q-item-section>
          </q-item>
          <q-item>
            <q-item-section avatar><q-icon name="water" color="blue-7" /></q-item-section>
            <q-item-section>Cantidad de lluvia</q-item-section>
            <q-item-section side>{{ diaSeleccionado.lluvia_mm != null ? diaSeleccionado.lluvia_mm.toFixed(1) : '0' }} mm</q-item-section>
          </q-item>
          <q-item>
            <q-item-section avatar><q-icon name="air" color="blue-grey-6" /></q-item-section>
            <q-item-section>Viento máximo</q-item-section>
            <q-item-section side>{{ Math.round(diaSeleccionado.viento_max) }} km/h</q-item-section>
          </q-item>
          <q-item>
            <q-item-section avatar><q-icon name="wb_sunny" color="amber-8" /></q-item-section>
            <q-item-section>Índice UV</q-item-section>
            <q-item-section side>{{ diaSeleccionado.indice_uv != null ? Math.round(diaSeleccionado.indice_uv) : '-' }}</q-item-section>
          </q-item>
          <q-item>
            <q-item-section avatar><q-icon name="wb_twilight" color="deep-orange-5" /></q-item-section>
            <q-item-section>Amanece / anochece</q-item-section>
            <q-item-section side>{{ formatearHora(diaSeleccionado.amanecer) }} - {{ formatearHora(diaSeleccionado.atardecer) }}</q-item-section>
          </q-item>
        </q-list>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="brown-8" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo: nueva observacion / editar observacion -->
    <q-dialog v-model="dlgObservacion">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">{{ observacionEditando ? 'Editar observación' : 'Nueva observación' }}</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="formObs.fecha" label="Fecha" dense outlined readonly>
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="formObs.fecha" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input v-model="formObs.nota" label="Nota" type="textarea" dense outlined placeholder="¿Qué observaste en el cultivo? (plagas, estado de las plantas, etc.)" />
          <q-file
            v-model="formObs.foto"
            :label="observacionEditando ? 'Cambiar foto (opcional)' : 'Foto (opcional)'"
            dense
            outlined
            accept="image/*"
            clearable
            @update:model-value="actualizarVistaPrevia"
          >
            <template v-slot:prepend><q-icon name="photo_camera" /></template>
          </q-file>
          <q-img v-if="vistaPreviaFoto" :src="vistaPreviaFoto" class="rounded-borders vista-previa-foto" fit="cover" />
          <div v-else-if="observacionEditando?.foto_url" class="text-caption text-grey-7">
            <q-img :src="urlCompleta(observacionEditando.foto_url)" class="rounded-borders vista-previa-foto q-mb-xs" fit="cover" />
            Foto actual (elige una nueva arriba para reemplazarla)
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardando" @click="guardarObservacion" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Visor: ver la foto de una observacion en grande, con zoom de dos dedos,
         botones +/- y arrastre para moverse por la foto acercada (mouse y dedo) -->
    <q-dialog v-model="dlgFotoGrande" @hide="restablecerVisorFoto" maximized transition-show="fade" transition-hide="fade">
      <q-card class="visor-foto-card">
        <div
          class="visor-foto"
          :class="{ 'visor-foto--arrastrable': escalaFoto > 1 }"
          @touchstart="iniciarGestoFoto"
          @touchmove="moverGestoFoto"
          @touchend="terminarArrastreFoto"
          @mousedown="iniciarArrastreFoto"
          @mousemove="moverArrastreFoto"
          @mouseup="terminarArrastreFoto"
          @mouseleave="terminarArrastreFoto"
        >
          <div class="foto-marco">
            <img
              ref="imgFotoRef"
              :src="fotoGrandeUrl"
              class="foto-grande-img"
              draggable="false"
              :style="{ transform: `translate(${posFoto.x}px, ${posFoto.y}px) scale(${escalaFoto})` }"
            />
          </div>
        </div>

        <q-btn round color="white" text-color="brown-9" icon="close" class="visor-foto-cerrar" v-close-popup />

        <div class="visor-foto-controles">
          <q-btn round color="brown-8" icon="remove" @click="acercarFoto(-0.5)" />
          <q-btn round color="brown-8" icon="refresh" size="sm" @click="restablecerVisorFoto" />
          <q-btn round color="brown-8" icon="add" @click="acercarFoto(0.5)" />
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useQuasar } from 'quasar'
import { useProduccionStore } from '@/stores/produccion'
import { useMonitoreoStore } from '@/stores/monitoreo'

const $q = useQuasar()
const produccion = useProduccionStore()
const monitoreo = useMonitoreoStore()

const cargandoParcelas = ref(true)
const parcelaSeleccionada = ref(null)
const errorClima = ref('')
const guardando = ref(false)

const opcionesParcela = computed(() =>
  produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
)

const observacionesDeParcela = computed(() =>
  monitoreo.observaciones.filter(o => o.parcela_id === parcelaSeleccionada.value)
)

async function cargarTodoDeParcela (parcelaId) {
  if (!parcelaId) return
  errorClima.value = ''
  try {
    await monitoreo.cargarClima(parcelaId)
  } catch (err) {
    errorClima.value = err.response?.data?.message || 'No se pudo consultar el clima de esta parcela.'
  }
  await monitoreo.cargarObservaciones(parcelaId)
}

watch(parcelaSeleccionada, (id) => {
  cargarTodoDeParcela(id)
})

// Iconos del set clasico "material-icons" (el que usa el proyecto). "rainy"
// no existe en ese set (es de Material Symbols) y por eso no se veia: se
// cambia por "grain", que si es un icono valido del set clasico.
function iconoClima (probabilidadLluvia) {
  if (probabilidadLluvia >= 60) return 'grain'
  if (probabilidadLluvia >= 30) return 'wb_cloudy'
  return 'wb_sunny'
}

function colorClima (probabilidadLluvia) {
  if (probabilidadLluvia >= 60) return 'blue-8'
  if (probabilidadLluvia >= 30) return 'blue-grey-6'
  return 'amber-8'
}

// Clase para el efecto de color al pasar el mouse por cada tarjeta del
// pronostico: cada clima tiene su propio brillo (sol=dorado, nube=gris
// azulado, lluvia=azul).
function claseClima (probabilidadLluvia) {
  if (probabilidadLluvia >= 60) return 'clima-lluvia'
  if (probabilidadLluvia >= 30) return 'clima-nube'
  return 'clima-sol'
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

function formatearDiaCorto (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO', { weekday: 'short', day: 'numeric' })
}

function formatearDiaLargo (fecha) {
  if (!fecha) return ''
  const texto = new Date(fecha).toLocaleDateString('es-BO', { weekday: 'long', day: 'numeric', month: 'long' })
  return texto.charAt(0).toUpperCase() + texto.slice(1)
}

function formatearHora (fechaHora) {
  if (!fechaHora) return '-'
  return new Date(fechaHora).toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}

// --- Detalle de un dia del pronostico ---
const dlgDetalleDia = ref(false)
const diaSeleccionado = ref(null)

function verDetalleDia (dia) {
  diaSeleccionado.value = dia
  dlgDetalleDia.value = true
}

function urlCompleta (fotoUrl) {
  // foto_url viene como "/storage/observaciones/xxx.jpg" (ruta relativa del backend)
  return `http://${window.location.hostname}:8000${fotoUrl}`
}

// --- Ver la foto de una observacion en grande, con zoom (dedos o botones
//     +/-) y arrastre para moverse por la foto acercada (mouse o dedo) ---
const dlgFotoGrande = ref(false)
const fotoGrandeUrl = ref('')
const escalaFoto = ref(1)
const posFoto = ref({ x: 0, y: 0 })
const imgFotoRef = ref(null)
let distanciaInicialPinch = 0
let escalaInicialPinch = 1
let arrastrando = false
let inicioArrastre = { x: 0, y: 0 }
let posInicioArrastre = { x: 0, y: 0 }

function verFotoGrande (fotoUrl) {
  fotoGrandeUrl.value = urlCompleta(fotoUrl)
  restablecerVisorFoto()
  dlgFotoGrande.value = true
}

function restablecerVisorFoto () {
  escalaFoto.value = 1
  posFoto.value = { x: 0, y: 0 }
}

// No dejar arrastrar la foto mas alla de su propio borde (si no, se queda
// "flotando" y detras se alcanza a ver el resto de la pantalla).
function limitarPosFoto (pos) {
  const el = imgFotoRef.value
  if (!el) return pos
  const excesoX = Math.max(0, (el.offsetWidth * (escalaFoto.value - 1)) / 2)
  const excesoY = Math.max(0, (el.offsetHeight * (escalaFoto.value - 1)) / 2)
  return {
    x: Math.min(Math.max(pos.x, -excesoX), excesoX),
    y: Math.min(Math.max(pos.y, -excesoY), excesoY)
  }
}

// Mientras el visor esta abierto, la pagina de fondo no debe poder
// desplazarse (si no, se alcanza a ver el resto de la app detras).
watch(dlgFotoGrande, (abierto) => {
  document.body.style.overflow = abierto ? 'hidden' : ''
})

function distanciaEntreDedos (touches) {
  const dx = touches[0].clientX - touches[1].clientX
  const dy = touches[0].clientY - touches[1].clientY
  return Math.hypot(dx, dy)
}

// Botones +/- del visor (sirve tambien en compu, sin necesidad de dedos)
function acercarFoto (incremento) {
  escalaFoto.value = Math.min(Math.max(escalaFoto.value + incremento, 1), 4)
  posFoto.value = limitarPosFoto(posFoto.value)
}

// --- Gestos tactiles (celular): un dedo mueve la foto acercada, dos dedos hacen zoom ---
function iniciarGestoFoto (e) {
  if (e.touches.length === 2) {
    distanciaInicialPinch = distanciaEntreDedos(e.touches)
    escalaInicialPinch = escalaFoto.value
  } else if (e.touches.length === 1 && escalaFoto.value > 1) {
    arrastrando = true
    inicioArrastre = { x: e.touches[0].clientX, y: e.touches[0].clientY }
    posInicioArrastre = { ...posFoto.value }
  }
}

function moverGestoFoto (e) {
  if (e.touches.length === 2) {
    e.preventDefault()
    const factor = distanciaEntreDedos(e.touches) / distanciaInicialPinch
    escalaFoto.value = Math.min(Math.max(escalaInicialPinch * factor, 1), 4)
    posFoto.value = limitarPosFoto(posFoto.value)
  } else if (e.touches.length === 1 && arrastrando) {
    e.preventDefault()
    posFoto.value = limitarPosFoto({
      x: posInicioArrastre.x + (e.touches[0].clientX - inicioArrastre.x),
      y: posInicioArrastre.y + (e.touches[0].clientY - inicioArrastre.y)
    })
  }
}

// --- Arrastre con mouse (compu): click sostenido y mover para desplazar la foto acercada ---
function iniciarArrastreFoto (e) {
  if (escalaFoto.value <= 1) return
  e.preventDefault() // evita que el navegador intente seleccionar texto o arrastrar la imagen
  arrastrando = true
  inicioArrastre = { x: e.clientX, y: e.clientY }
  posInicioArrastre = { ...posFoto.value }
}

function moverArrastreFoto (e) {
  if (!arrastrando) return
  e.preventDefault()
  posFoto.value = limitarPosFoto({
    x: posInicioArrastre.x + (e.clientX - inicioArrastre.x),
    y: posInicioArrastre.y + (e.clientY - inicioArrastre.y)
  })
}

function terminarArrastreFoto () {
  arrastrando = false
}

// --- Nueva observacion / editar observacion ---
const dlgObservacion = ref(false)
const formObs = ref({ fecha: '', nota: '', foto: null })
const vistaPreviaFoto = ref(null)
const observacionEditando = ref(null) // null = creando una nueva; si tiene valor, se esta editando esa

function abrirNuevaObservacion () {
  observacionEditando.value = null
  formObs.value = { fecha: '', nota: '', foto: null }
  limpiarVistaPrevia()
  dlgObservacion.value = true
}

function abrirEditarObservacion (obs) {
  observacionEditando.value = obs
  formObs.value = { fecha: obs.fecha, nota: obs.nota, foto: null }
  limpiarVistaPrevia()
  dlgObservacion.value = true
}

// Muestra la foto elegida antes de guardar (el input de archivo solo
// muestra el nombre, no una imagen, asi que se arma una vista previa local).
function actualizarVistaPrevia (archivo) {
  limpiarVistaPrevia()
  if (archivo) {
    vistaPreviaFoto.value = URL.createObjectURL(archivo)
  }
}

function limpiarVistaPrevia () {
  if (vistaPreviaFoto.value) {
    URL.revokeObjectURL(vistaPreviaFoto.value)
  }
  vistaPreviaFoto.value = null
}

async function guardarObservacion () {
  if (!formObs.value.fecha || !formObs.value.nota) {
    $q.notify({ type: 'negative', message: 'Completa la fecha y la nota.' })
    return
  }
  guardando.value = true
  try {
    if (observacionEditando.value) {
      await monitoreo.actualizarObservacion(observacionEditando.value.id, {
        fecha: formObs.value.fecha,
        nota: formObs.value.nota,
        foto: formObs.value.foto
      })
      $q.notify({ type: 'positive', message: 'Observación actualizada.' })
    } else {
      await monitoreo.crearObservacion({
        parcela_id: parcelaSeleccionada.value,
        fecha: formObs.value.fecha,
        nota: formObs.value.nota,
        foto: formObs.value.foto
      })
      $q.notify({ type: 'positive', message: 'Observación guardada.' })
    }
    limpiarVistaPrevia()
    dlgObservacion.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar la observación.' })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminarObservacion (obs) {
  $q.dialog({
    title: 'Eliminar observación',
    message: '¿Seguro que quieres eliminar esta observación?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await monitoreo.eliminarObservacion(obs.id)
      $q.notify({ type: 'positive', message: 'Observación eliminada.' })
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo eliminar.' })
    }
  })
}

onMounted(async () => {
  cargandoParcelas.value = true
  if (produccion.parcelas.length === 0) {
    await produccion.cargarParcelas()
  }
  cargandoParcelas.value = false
  if (produccion.parcelas.length > 0) {
    parcelaSeleccionada.value = produccion.parcelas[0].id
  }
})

// Por si se sale de esta pagina con el visor de foto todavia abierto, que
// no se quede la pagina de fondo bloqueada sin poder desplazarse.
onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
.clima-actual {
  background: linear-gradient(160deg, #fdf1d9 0%, #f6dfab 100%);
  border-color: rgba(184, 132, 42, 0.4) !important;
}

/* En compu las tarjetas se reparten parejas en una sola fila (sin importar
   si son 5 o 7 dias). En celular no caben todas parejas, asi que en vez de
   amontonarse en filas desiguales, se desliza en horizontal (como el
   pronostico de clima de un celular normal). */
.fila-pronostico {
  display: flex;
  gap: 8px;
}
@media (min-width: 600px) {
  .fila-pronostico {
    flex-wrap: nowrap;
  }
  .fila-pronostico .pronostico-dia {
    flex: 1 1 0;
    min-width: 0;
  }
}
@media (max-width: 599px) {
  .fila-pronostico {
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
  }
  .fila-pronostico .pronostico-dia {
    flex: 0 0 auto;
    width: 96px;
  }
}

.pronostico-dia {
  background: linear-gradient(160deg, #fdf1d9 0%, #f6dfab 100%);
  border-color: rgba(184, 132, 42, 0.4) !important;
  transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

/* Efecto de color al pasar el mouse (o al tocar en celular): cada tarjeta
   se "enciende" con el color de su propio clima y se levanta un poco. */
.pronostico-dia.clima-sol:hover,
.pronostico-dia.clima-sol:focus-visible {
  background: linear-gradient(160deg, #ffd77a 0%, #f5a623 100%);
  border-color: #e08e00 !important;
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(245, 166, 35, 0.45);
}
.pronostico-dia.clima-nube:hover,
.pronostico-dia.clima-nube:focus-visible {
  background: linear-gradient(160deg, #cfd8e3 0%, #90a4ae 100%);
  border-color: #62727b !important;
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(96, 125, 139, 0.45);
}
.pronostico-dia.clima-lluvia:hover,
.pronostico-dia.clima-lluvia:focus-visible {
  background: linear-gradient(160deg, #81d4fa 0%, #0277bd 100%);
  border-color: #01579b !important;
  transform: translateY(-4px);
  box-shadow: 0 8px 18px rgba(2, 119, 189, 0.45);
}
.pronostico-dia:hover .text-brown-8,
.pronostico-dia:hover .text-grey-7,
.pronostico-dia:focus-visible .text-brown-8,
.pronostico-dia:focus-visible .text-grey-7 {
  color: #fff !important;
}

.vista-previa-foto {
  max-width: 180px;
  height: 130px;
  border: 1px solid rgba(184, 132, 42, 0.4);
}

.foto-observacion {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.foto-observacion:hover {
  transform: scale(1.08);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.visor-foto-card {
  background: rgba(20, 14, 8, 0.94);
  position: relative;
  width: 100%;
  height: 100%;
}
.visor-foto {
  touch-action: none;
  overflow: hidden;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
/* Marco con un pequeño margen respecto a la pantalla (para que no quede
   pegado borde a borde), pero la foto de adentro SIEMPRE lo llena por
   completo (object-fit: cover recorta lo que sobra en vez de dejar franjas
   vacias) - asi nunca se ve un "borde" de fondo al hacer zoom o arrastrar. */
.foto-marco {
  width: 92vw;
  height: 90vh;
  max-width: 100%;
  max-height: 100%;
  overflow: hidden;
  position: relative;
}
.foto-grande-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  touch-action: none;
  transition: transform 0.05s linear;
  user-select: none;
}
.visor-foto--arrastrable {
  cursor: grab;
}
.visor-foto--arrastrable:active {
  cursor: grabbing;
}
.visor-foto-cerrar {
  position: absolute;
  top: 12px;
  right: 12px;
}
.visor-foto-controles {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.12);
  padding: 8px 16px;
  border-radius: 999px;
}
</style>
