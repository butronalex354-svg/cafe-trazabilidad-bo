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

          <div class="fila-pronostico">
            <q-card v-for="dia in monitoreo.clima.pronostico" :key="dia.fecha" flat bordered class="pronostico-dia">
              <q-card-section class="text-center q-pa-sm">
                <div class="text-caption text-brown-8 text-weight-bold">{{ formatearDiaCorto(dia.fecha) }}</div>
                <q-icon :name="iconoClima(dia.probabilidad_lluvia)" size="22px" :color="colorClima(dia.probabilidad_lluvia)" class="q-my-xs" />
                <div class="text-caption">{{ Math.round(dia.temperatura_max) }}° / {{ Math.round(dia.temperatura_min) }}°</div>
                <div class="text-caption text-grey-7">{{ Math.round(dia.probabilidad_lluvia) }}% lluvia</div>
              </q-card-section>
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
              <q-avatar square size="48px" rounded>
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
              <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarObservacion(obs)" />
            </q-item-section>
          </q-item>
        </q-list>
      </template>
    </template>

    <!-- Dialogo: nueva observacion -->
    <q-dialog v-model="dlgObservacion">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">Nueva observación</q-card-section>
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
          <q-file v-model="formObs.foto" label="Foto (opcional)" dense outlined accept="image/*" clearable>
            <template v-slot:prepend><q-icon name="photo_camera" /></template>
          </q-file>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardando" @click="guardarObservacion" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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

function iconoClima (probabilidadLluvia) {
  if (probabilidadLluvia >= 60) return 'rainy'
  if (probabilidadLluvia >= 30) return 'cloud'
  return 'wb_sunny'
}

function colorClima (probabilidadLluvia) {
  if (probabilidadLluvia >= 60) return 'blue-8'
  if (probabilidadLluvia >= 30) return 'blue-grey-6'
  return 'amber-8'
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

function formatearDiaCorto (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO', { weekday: 'short', day: 'numeric' })
}

function urlCompleta (fotoUrl) {
  // foto_url viene como "/storage/observaciones/xxx.jpg" (ruta relativa del backend)
  return `http://${window.location.hostname}:8000${fotoUrl}`
}

// --- Nueva observacion ---
const dlgObservacion = ref(false)
const formObs = ref({ fecha: '', nota: '', foto: null })

function abrirNuevaObservacion () {
  formObs.value = { fecha: '', nota: '', foto: null }
  dlgObservacion.value = true
}

async function guardarObservacion () {
  if (!formObs.value.fecha || !formObs.value.nota) {
    $q.notify({ type: 'negative', message: 'Completa la fecha y la nota.' })
    return
  }
  guardando.value = true
  try {
    await monitoreo.crearObservacion({
      parcela_id: parcelaSeleccionada.value,
      fecha: formObs.value.fecha,
      nota: formObs.value.nota,
      foto: formObs.value.foto
    })
    $q.notify({ type: 'positive', message: 'Observación guardada.' })
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
}
</style>
