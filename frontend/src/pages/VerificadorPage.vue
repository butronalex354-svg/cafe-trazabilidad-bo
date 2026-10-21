<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-brown-9 q-mb-xs">{{ tituloSeccion }}</div>
        <div class="text-caption text-grey-7">Revisá el origen, procesamiento y calidad de cada lote antes de verificarlo</div>
      </div>
      <div class="contador-solicitudes">{{ verificacion.solicitudes.length }}</div>
    </div>

    <q-inner-loading :showing="verificacion.cargando" />

    <div v-if="!verificacion.cargando && verificacion.solicitudes.length === 0" class="caja-tema rounded-borders q-pa-xl text-center estado-vacio">
      <q-icon :name="iconoVacio" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">{{ tituloVacio }}</div>
      <div class="text-caption text-grey-7 q-mt-xs" style="max-width: 420px; margin: 0 auto">{{ mensajeVacio }}</div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="s in verificacion.solicitudes" :key="s.id" clickable @click="abrirDetalle(s)">
        <q-item-section avatar>
          <q-icon :name="iconoEstado(s.estado)" :color="colorEstado(s.estado)" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ s.cosecha?.codigo_trazabilidad }} · {{ s.cosecha?.variedad_cafe }}</q-item-label>
          <q-item-label caption>
            {{ s.cosecha?.parcela?.productor?.nombre_completo }} · {{ s.cosecha?.parcela?.nombre_parcela }}
          </q-item-label>
          <q-item-label caption>Solicitado: {{ formatearFecha(s.fecha_solicitud) }}</q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="column items-end q-gutter-xs">
            <q-badge :color="colorEstado(s.estado)" :label="etiquetaEstado(s.estado)" />
            <q-btn
              unelevated
              no-caps
              dense
              size="sm"
              color="brown-8"
              icon="rate_review"
              label="Revisar"
              @click.stop="abrirDetalle(s)"
            />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Dialogo de detalle / resolucion -->
    <q-dialog v-model="dlgDetalle" @hide="verificacion.detalle = null">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 640px; width: 100%">
        <q-card-section v-if="!verificacion.detalle" class="text-center q-pa-lg">
          <q-spinner color="brown-8" size="2em" />
        </q-card-section>

        <template v-else>
          <q-card-section>
            <div class="text-h6 text-brown-9">{{ verificacion.detalle.verificacion.cosecha.codigo_trazabilidad }}</div>
            <div class="text-caption text-grey-7">
              {{ verificacion.detalle.verificacion.cosecha.parcela.productor?.nombre_completo }} ·
              {{ verificacion.detalle.verificacion.cosecha.parcela.nombre_parcela }}
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 text-brown-8 q-mb-xs">Origen (ubicación de la parcela)</div>
            <ParcelaMapView
              v-if="verificacion.detalle.verificacion.cosecha.parcela.ubicacion"
              :ubicacion="verificacion.detalle.verificacion.cosecha.parcela.ubicacion"
              :etiqueta="verificacion.detalle.verificacion.cosecha.parcela.nombre_parcela"
              :height="160"
              fill
            />
            <div v-else class="text-grey-7 text-caption">Esta parcela no tiene ubicación GPS registrada.</div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 text-brown-8 q-mb-xs">Producción</div>
            <div class="text-body2">
              {{ verificacion.detalle.verificacion.cosecha.cantidad }} kg ·
              {{ verificacion.detalle.verificacion.cosecha.variedad_cafe }} ·
              Cosechado: {{ formatearFecha(verificacion.detalle.verificacion.cosecha.fecha) }}
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 text-brown-8 q-mb-xs">Procesamiento</div>
            <div v-if="verificacion.detalle.verificacion.cosecha.etapas_procesamiento?.length === 0" class="text-grey-7 text-caption">
              No se registraron etapas de procesamiento.
            </div>
            <q-list v-else dense>
              <q-item v-for="e in verificacion.detalle.verificacion.cosecha.etapas_procesamiento" :key="e.id">
                <q-item-section>{{ e.etapa }} · {{ formatearFecha(e.fecha) }} · {{ e.responsable }}</q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 text-brown-8 q-mb-xs">Calidad</div>
            <div v-if="verificacion.detalle.verificacion.cosecha.controles_calidad?.length === 0 && verificacion.detalle.diagnosticos_parcela.length === 0" class="text-grey-7 text-caption">
              No hay controles de calidad ni diagnósticos recientes de la parcela.
            </div>
            <q-list v-else dense>
              <q-item v-for="c in verificacion.detalle.verificacion.cosecha.controles_calidad" :key="'cc-' + c.id">
                <q-item-section>Control de calidad: humedad {{ c.humedad }}%, {{ c.defectos }} defectos, clasificación {{ c.clasificacion }}</q-item-section>
              </q-item>
              <q-item v-for="d in verificacion.detalle.diagnosticos_parcela" :key="'d-' + d.id">
                <q-item-section>Diagnóstico IA ({{ formatearFecha(d.fecha) }}): {{ d.resultado }} ({{ d.confianza }}%)</q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 text-brown-8 q-mb-sm">Resolución</div>
            <q-select
              v-model="formResolver.estado"
              :options="opcionesResolucion"
              emit-value
              map-options
              label="Nuevo estado"
              dense
              outlined
              bg-color="white"
              class="q-mb-sm"
            />
            <q-input
              v-model="formResolver.observaciones"
              label="Observaciones / incidencias (para el productor)"
              type="textarea"
              rows="3"
              dense
              outlined
              bg-color="white"
            />
          </q-card-section>

          <q-card-actions align="right" class="q-pb-md q-px-md">
            <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
            <q-btn unelevated color="brown-8" label="Guardar resolución" no-caps :loading="resolviendo" @click="guardarResolucion" />
          </q-card-actions>
        </template>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useVerificacionStore } from '@/stores/verificacion'
import ParcelaMapView from '@/components/ParcelaMapView.vue'

const $q = useQuasar()
const route = useRoute()
const verificacion = useVerificacionStore()

const titulosPorEstado = {
  pendiente: 'Pendientes',
  observado: 'Observados',
  verificado: 'Verificados',
  rechazado: 'Rechazados'
}
const tituloSeccion = computed(() => titulosPorEstado[route.meta.estadoFiltro] || 'Todas las solicitudes')

const vaciosPorEstado = {
  pendiente: {
    icono: 'inbox',
    titulo: 'No hay solicitudes pendientes',
    mensaje: 'Esta bandeja se llena cuando un productor hace clic en "Solicitar verificación" sobre uno de sus lotes, en la sección Producción.'
  },
  observado: {
    icono: 'visibility',
    titulo: 'No hay lotes observados',
    mensaje: 'Acá aparecen los lotes a los que les pediste una corrección. En cuanto marques una solicitud como "Observado", va a salir en esta lista.'
  },
  verificado: {
    icono: 'check_circle',
    titulo: 'Todavía no verificaste ningún lote',
    mensaje: 'En cuanto apruebes una solicitud como "Verificado", el lote va a aparecer acá.'
  },
  rechazado: {
    icono: 'cancel',
    titulo: 'No hay lotes rechazados',
    mensaje: 'Los lotes que marques como "Rechazado" van a aparecer en esta lista.'
  }
}
const vacioPorDefecto = {
  icono: 'inbox',
  titulo: 'Todavía no hay solicitudes de verificación',
  mensaje: 'Van a aparecer acá apenas un productor solicite verificar uno de sus lotes desde la sección Producción.'
}
const iconoVacio = computed(() => (vaciosPorEstado[route.meta.estadoFiltro] || vacioPorDefecto).icono)
const tituloVacio = computed(() => (vaciosPorEstado[route.meta.estadoFiltro] || vacioPorDefecto).titulo)
const mensajeVacio = computed(() => (vaciosPorEstado[route.meta.estadoFiltro] || vacioPorDefecto).mensaje)

// Recarga la lista cada vez que se navega entre Todas/Pendientes/Observados/etc,
// ya que las 5 rutas comparten este mismo componente (Vue lo reutiliza en vez
// de recrearlo, asi que un simple onMounted no alcanza para las siguientes).
watch(() => route.meta.estadoFiltro, (estado) => {
  verificacion.cargarSolicitudes(estado)
}, { immediate: true })

const opcionesResolucion = [
  { label: 'Verificado', value: 'verificado' },
  { label: 'Observado (pide correcciones)', value: 'observado' },
  { label: 'Rechazado', value: 'rechazado' }
]

function etiquetaEstado (estado) {
  return { pendiente: 'Pendiente', observado: 'Observado', verificado: 'Verificado', rechazado: 'Rechazado' }[estado] || estado
}

function iconoEstado (estado) {
  return { pendiente: 'schedule', observado: 'visibility', verificado: 'check_circle', rechazado: 'cancel' }[estado] || 'help'
}

function colorEstado (estado) {
  return { pendiente: 'amber-8', observado: 'orange-9', verificado: 'positive', rechazado: 'negative' }[estado] || 'grey'
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const dlgDetalle = ref(false)
const resolviendo = ref(false)
const formResolver = ref({ estado: 'verificado', observaciones: '' })
const solicitudActual = ref(null)

async function abrirDetalle (s) {
  solicitudActual.value = s
  formResolver.value = { estado: 'verificado', observaciones: '' }
  dlgDetalle.value = true
  await verificacion.cargarDetalle(s.id)
}

async function guardarResolucion () {
  resolviendo.value = true
  try {
    await verificacion.resolver(solicitudActual.value.id, formResolver.value)
    $q.notify({ type: 'positive', message: 'Verificación actualizada.' })
    dlgDetalle.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar la resolución.' })
  } finally {
    resolviendo.value = false
  }
}

</script>

<style scoped>
.estado-vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.contador-solicitudes {
  min-width: 2.75rem;
  height: 2.75rem;
  padding: 0 0.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  background: var(--gradiente-icono);
  box-shadow: 0 3px 8px rgba(138, 90, 30, 0.35);
}
</style>
