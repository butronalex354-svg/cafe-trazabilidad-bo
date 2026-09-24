<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Calidad</div>
    <div class="text-caption text-grey-7 q-mb-md">Diagnóstico con IA, alertas y control de calidad del lote</div>

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
        <!-- Resumen -->
        <div class="row q-col-gutter-md q-mb-md items-stretch">
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="fact_check" color="white" size="20px" /></div>
                <div class="stat-numero">{{ diagnosticosDeParcela.length }}</div>
                <div class="stat-label">Diagnósticos</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="bug_report" color="white" size="20px" /></div>
                <div class="stat-numero">{{ diagnosticosConProblema }}</div>
                <div class="stat-label">Con problema detectado</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="notifications_active" color="white" size="20px" /></div>
                <div class="stat-numero">{{ calidad.alertasNoLeidas }}</div>
                <div class="stat-label">Alertas sin leer</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="event" color="white" size="20px" /></div>
                <div class="stat-numero stat-numero--fecha">{{ fechaUltimoDiagnostico }}</div>
                <div class="stat-label">Último diagnóstico</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Diagnostico con IA -->
        <div class="row items-center justify-between q-mb-sm">
          <div class="text-h6 text-brown-8">Diagnóstico con IA</div>
          <q-btn unelevated color="brown-8" icon="add_a_photo" label="Subir foto" @click="abrirNuevoDiagnostico" />
        </div>
        <div class="text-caption text-grey-7 q-mb-sm">
          <q-icon name="smart_toy" size="14px" /> Diagnóstico generado por un modelo de IA real, entrenado con fotos de café (roya y ácaros).
        </div>

        <q-inner-loading :showing="calidad.cargandoDiagnosticos" />

        <div v-if="!calidad.cargandoDiagnosticos && diagnosticosDeParcela.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders q-mb-lg">
          Sin diagnósticos registrados en esta parcela todavía.
        </div>

        <q-list v-else bordered separator class="rounded-borders caja-tema q-mb-lg">
          <q-item v-for="d in diagnosticosDeParcela" :key="d.id">
            <q-item-section avatar>
              <q-avatar square size="48px" rounded class="foto-diagnostico" @click="verFotoGrande(d.imagen_url)">
                <img :src="urlCompleta(d.imagen_url)" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>
                <q-badge :color="colorResultado(d.resultado)" :label="etiquetaResultado(d.resultado)" />
                <span class="text-caption text-grey-7 q-ml-sm">Confianza {{ Math.round(d.confianza) }}%</span>
              </q-item-label>
              <q-item-label caption>{{ formatearFecha(d.fecha) }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarDiagnostico(d)" />
            </q-item-section>
          </q-item>
        </q-list>

        <!-- Alertas -->
        <div ref="seccionAlertasRef" class="row items-center q-mb-sm">
          <div class="text-h6 text-brown-8">Alertas</div>
          <q-badge v-if="calidad.alertasNoLeidas > 0" color="negative" rounded class="q-ml-sm">{{ calidad.alertasNoLeidas }}</q-badge>
        </div>

        <q-inner-loading :showing="calidad.cargandoAlertas" />

        <div v-if="!calidad.cargandoAlertas && calidad.alertas.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders q-mb-lg">
          Sin alertas por ahora — cuando la IA detecte una plaga o enfermedad, va a aparecer aquí.
        </div>

        <q-list v-else bordered separator class="rounded-borders caja-tema q-mb-lg">
          <q-item
            v-for="a in calidad.alertas"
            :key="a.id"
            clickable
            :class="{ 'alerta-no-leida': !a.resuelta }"
            @click="abrirDetalleAlerta(a)"
          >
            <q-item-section avatar>
              <q-icon :name="a.resuelta ? 'task_alt' : 'notifications_active'" :color="a.resuelta ? 'grey-6' : 'negative'" />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="{ 'text-weight-bold': !a.resuelta }">{{ a.mensaje }}</q-item-label>
              <q-item-label caption>{{ formatearFecha(a.fecha) }} · {{ a.diagnostico?.parcela?.nombre_parcela }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge v-if="a.resuelta" color="positive" label="Resuelta" />
              <q-btn
                v-else
                flat
                dense
                size="sm"
                color="brown-8"
                label="Marcar resuelta"
                @click.stop="marcarResueltaDesdeLista(a)"
              />
            </q-item-section>
          </q-item>
        </q-list>

        <!-- Control de calidad -->
        <div class="row items-center justify-between q-mb-sm">
          <div class="text-h6 text-brown-8">Control de calidad del lote</div>
          <q-btn
            unelevated
            color="brown-8"
            icon="add"
            label="Nuevo control"
            :disable="!loteSeleccionado"
            @click="abrirNuevoControl"
          />
        </div>

        <q-select
          v-model="loteSeleccionado"
          :options="opcionesLote"
          emit-value
          map-options
          label="Lote (cosecha)"
          dense
          outlined
          bg-color="white"
          class="q-mb-sm"
          style="max-width: 360px"
          :disable="opcionesLote.length === 0"
          :hint="opcionesLote.length === 0 ? 'Esta parcela todavía no tiene lotes registrados' : ''"
        />

        <q-inner-loading :showing="calidad.cargandoControlesCalidad" />

        <div v-if="loteSeleccionado && !calidad.cargandoControlesCalidad && controlesDelLote.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
          Sin controles de calidad registrados en este lote todavía.
        </div>

        <q-list v-if="loteSeleccionado" bordered separator class="rounded-borders caja-tema">
          <q-item v-for="c in controlesDelLote" :key="c.id">
            <q-item-section>
              <q-item-label>
                <q-badge :color="colorClasificacion(c.clasificacion)" :label="c.clasificacion || 'Sin clasificar'" />
              </q-item-label>
              <q-item-label caption>
                Humedad {{ c.humedad != null ? c.humedad + '%' : '-' }} · Defectos {{ c.defectos ?? '-' }} · {{ formatearFecha(c.fecha) }}
              </q-item-label>
              <q-item-label v-if="c.observaciones" caption class="text-grey-7">{{ c.observaciones }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row no-wrap">
                <q-btn flat dense round size="sm" icon="edit" color="brown-7" @click="abrirEditarControl(c)" />
                <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarControl(c)" />
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </template>
    </template>

    <!-- Dialogo: nuevo diagnostico -->
    <q-dialog v-model="dlgDiagnostico">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">Subir foto para diagnóstico</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-file
            v-model="fotoDiagnostico"
            label="Foto de la planta"
            dense
            outlined
            accept="image/*"
            clearable
            @update:model-value="actualizarVistaPreviaDiagnostico"
          >
            <template v-slot:prepend><q-icon name="photo_camera" /></template>
          </q-file>
          <img v-if="vistaPreviaDiagnostico" :src="vistaPreviaDiagnostico" class="rounded-borders vista-previa-foto" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Diagnosticar" :loading="calidad.subiendoDiagnostico" @click="guardarDiagnostico" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo: nuevo control de calidad -->
    <q-dialog v-model="dlgControl">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">{{ controlEditando ? 'Editar control de calidad' : 'Nuevo control de calidad' }}</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="formControl.fecha" label="Fecha" dense outlined readonly>
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="formControl.fecha" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input v-model.number="formControl.humedad" label="Humedad (%)" type="number" step="0.1" min="0" max="100" dense outlined />
          <q-input v-model.number="formControl.defectos" label="Defectos encontrados" type="number" min="0" dense outlined />
          <q-select
            v-model="formControl.clasificacion"
            :options="opcionesClasificacion"
            emit-value
            map-options
            label="Clasificación"
            dense
            outlined
            clearable
          />
          <q-input v-model="formControl.observaciones" label="Observaciones" type="textarea" dense outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardandoControl" @click="guardarControl" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo: detalle de una alerta -->
    <q-dialog v-model="dlgDetalleAlerta">
      <q-card v-if="alertaSeleccionada" style="min-width: 300px; max-width: 400px" class="caja-tema">
        <q-card-section class="text-h6">Detalle de la alerta</q-card-section>
        <q-card-section v-if="alertaSeleccionada.diagnostico" class="q-pt-none">
          <img
            v-if="alertaSeleccionada.diagnostico.imagen_url"
            :src="urlCompleta(alertaSeleccionada.diagnostico.imagen_url)"
            class="rounded-borders foto-detalle-alerta cursor-pointer"
            @click="verFotoGrande(alertaSeleccionada.diagnostico.imagen_url)"
          />
          <div class="q-mt-sm">
            <q-badge :color="colorResultado(alertaSeleccionada.diagnostico.resultado)" :label="etiquetaResultado(alertaSeleccionada.diagnostico.resultado)" />
            <span class="text-caption text-grey-7 q-ml-sm">Confianza {{ Math.round(alertaSeleccionada.diagnostico.confianza) }}%</span>
          </div>
          <div class="text-caption text-grey-7 q-mt-xs">
            <q-icon name="map" size="14px" /> {{ alertaSeleccionada.diagnostico.parcela?.nombre_parcela }}
            · {{ formatearFecha(alertaSeleccionada.diagnostico.fecha) }}
          </div>
          <div class="text-body2 q-mt-sm">{{ alertaSeleccionada.mensaje }}</div>
          <q-badge v-if="alertaSeleccionada.resuelta" color="positive" label="Ya marcada como resuelta" class="q-mt-sm" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            v-if="!alertaSeleccionada.resuelta"
            unelevated
            color="positive"
            icon="task_alt"
            label="Marcar como resuelta"
            @click="marcarResueltaDesdeDetalle"
          />
          <q-btn flat label="Cerrar" color="brown-8" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Visor de foto grande -->
    <q-dialog v-model="dlgFotoGrande" maximized transition-show="fade" transition-hide="fade">
      <q-card class="visor-foto-card">
        <div class="visor-foto">
          <img :src="fotoGrandeUrl" class="foto-grande-img" />
        </div>
        <q-btn round color="white" text-color="brown-9" icon="close" class="visor-foto-cerrar" v-close-popup />
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { useProduccionStore } from '@/stores/produccion'
import { useCalidadStore } from '@/stores/calidad'

const $q = useQuasar()
const route = useRoute()
const produccion = useProduccionStore()
const calidad = useCalidadStore()

const cargandoParcelas = ref(true)
const parcelaSeleccionada = ref(null)
const seccionAlertasRef = ref(null)

const opcionesParcela = computed(() =>
  produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
)

const diagnosticosDeParcela = computed(() =>
  calidad.diagnosticos.filter(d => d.parcela_id === parcelaSeleccionada.value)
)

const diagnosticosConProblema = computed(() =>
  diagnosticosDeParcela.value.filter(d => d.resultado !== 'sana').length
)

const fechaUltimoDiagnostico = computed(() => {
  if (diagnosticosDeParcela.value.length === 0) return '-'
  // el historial ya viene ordenado del backend (mas nuevo primero)
  return formatearFecha(diagnosticosDeParcela.value[0].fecha)
})

function urlCompleta (rutaRelativa) {
  return `http://${window.location.hostname}:8000${rutaRelativa}`
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

function etiquetaResultado (resultado) {
  return { sana: 'Sana', plaga: 'Plaga', enfermedad: 'Enfermedad' }[resultado] || resultado
}

function colorResultado (resultado) {
  return { sana: 'positive', plaga: 'orange-8', enfermedad: 'negative' }[resultado] || 'grey'
}

function colorClasificacion (clasificacion) {
  return {
    primera: 'positive',
    segunda: 'amber-8',
    tercera: 'orange-9',
    exportacion: 'brown-8'
  }[clasificacion] || 'grey-6'
}

async function cargarTodoDeParcela (parcelaId) {
  if (!parcelaId) return
  await calidad.cargarDiagnosticos(parcelaId)
  if (produccion.cosechas.filter(c => c.parcela_id === parcelaId).length === 0) {
    await produccion.cargarCosechas(parcelaId)
  }
  loteSeleccionado.value = null
}

watch(parcelaSeleccionada, (id) => {
  cargarTodoDeParcela(id)
})

// --- Diagnostico ---
const dlgDiagnostico = ref(false)
const fotoDiagnostico = ref(null)
const vistaPreviaDiagnostico = ref(null)

function abrirNuevoDiagnostico () {
  fotoDiagnostico.value = null
  limpiarVistaPreviaDiagnostico()
  dlgDiagnostico.value = true
}

function actualizarVistaPreviaDiagnostico (archivo) {
  limpiarVistaPreviaDiagnostico()
  if (archivo) vistaPreviaDiagnostico.value = URL.createObjectURL(archivo)
}

function limpiarVistaPreviaDiagnostico () {
  if (vistaPreviaDiagnostico.value) URL.revokeObjectURL(vistaPreviaDiagnostico.value)
  vistaPreviaDiagnostico.value = null
}

async function guardarDiagnostico () {
  if (!fotoDiagnostico.value) {
    $q.notify({ type: 'negative', message: 'Elige una foto primero.' })
    return
  }
  try {
    const resultado = await calidad.subirDiagnostico({
      parcela_id: parcelaSeleccionada.value,
      foto: fotoDiagnostico.value
    })
    limpiarVistaPreviaDiagnostico()
    dlgDiagnostico.value = false
    if (resultado.resultado === 'sana') {
      $q.notify({ type: 'positive', message: 'Diagnóstico: planta sana.' })
    } else {
      $q.notify({ type: 'warning', message: `Diagnóstico: posible ${etiquetaResultado(resultado.resultado).toLowerCase()}. Se generó una alerta.` })
      await calidad.cargarAlertas()
    }
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo procesar el diagnóstico.' })
  }
}

function confirmarEliminarDiagnostico (diagnostico) {
  $q.dialog({
    title: 'Eliminar diagnóstico',
    message: '¿Seguro que quieres eliminar este diagnóstico? Si generó una alerta, también se elimina.',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await calidad.eliminarDiagnostico(diagnostico.id)
      await calidad.cargarAlertas()
      $q.notify({ type: 'positive', message: 'Diagnóstico eliminado.' })
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo eliminar.' })
    }
  })
}

// --- Foto grande ---
const dlgFotoGrande = ref(false)
const fotoGrandeUrl = ref('')
function verFotoGrande (rutaRelativa) {
  fotoGrandeUrl.value = urlCompleta(rutaRelativa)
  dlgFotoGrande.value = true
}

// --- Detalle de alerta ---
const dlgDetalleAlerta = ref(false)
const alertaSeleccionada = ref(null)
function abrirDetalleAlerta (alerta) {
  alertaSeleccionada.value = alerta
  dlgDetalleAlerta.value = true
  if (!alerta.leida) calidad.marcarAlertaLeida(alerta.id)
}

async function marcarResueltaDesdeLista (alerta) {
  try {
    await calidad.marcarAlertaResuelta(alerta.id)
    $q.notify({ type: 'positive', message: 'Alerta marcada como resuelta.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo actualizar la alerta.' })
  }
}

async function marcarResueltaDesdeDetalle () {
  await marcarResueltaDesdeLista(alertaSeleccionada.value)
  alertaSeleccionada.value = calidad.alertas.find(a => a.id === alertaSeleccionada.value.id) || alertaSeleccionada.value
}

// --- Control de calidad ---
const loteSeleccionado = ref(null)
const dlgControl = ref(false)
const guardandoControl = ref(false)
const opcionesClasificacion = [
  { label: 'Primera', value: 'primera' },
  { label: 'Segunda', value: 'segunda' },
  { label: 'Tercera', value: 'tercera' },
  { label: 'Exportación', value: 'exportacion' }
]
const formControl = ref({ fecha: '', humedad: null, defectos: null, clasificacion: null, observaciones: '' })
const controlEditando = ref(null) // null = creando uno nuevo; si tiene valor, se esta editando ese

const cosechasDeParcela = computed(() =>
  produccion.cosechas.filter(c => c.parcela_id === parcelaSeleccionada.value)
)

const opcionesLote = computed(() =>
  cosechasDeParcela.value.map(c => ({
    label: `${c.variedad_cafe} · ${c.codigo_trazabilidad}`,
    value: c.id
  }))
)

const controlesDelLote = computed(() =>
  calidad.controlesCalidad.filter(c => c.cosecha_id === loteSeleccionado.value)
)

watch(loteSeleccionado, (id) => {
  if (id) calidad.cargarControlesCalidad(id)
})

function abrirNuevoControl () {
  controlEditando.value = null
  formControl.value = { fecha: '', humedad: null, defectos: null, clasificacion: null, observaciones: '' }
  dlgControl.value = true
}

function abrirEditarControl (control) {
  controlEditando.value = control
  formControl.value = {
    fecha: control.fecha,
    humedad: control.humedad != null ? Number(control.humedad) : null,
    defectos: control.defectos,
    clasificacion: control.clasificacion,
    observaciones: control.observaciones || ''
  }
  dlgControl.value = true
}

async function guardarControl () {
  if (!formControl.value.fecha) {
    $q.notify({ type: 'negative', message: 'Completa la fecha.' })
    return
  }
  guardandoControl.value = true
  try {
    if (controlEditando.value) {
      await calidad.actualizarControlCalidad(controlEditando.value.id, {
        cosecha_id: loteSeleccionado.value,
        ...formControl.value
      })
      $q.notify({ type: 'positive', message: 'Control de calidad actualizado.' })
    } else {
      await calidad.crearControlCalidad({
        cosecha_id: loteSeleccionado.value,
        ...formControl.value
      })
      $q.notify({ type: 'positive', message: 'Control de calidad guardado.' })
    }
    dlgControl.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar el control.' })
  } finally {
    guardandoControl.value = false
  }
}

function confirmarEliminarControl (control) {
  $q.dialog({
    title: 'Eliminar control de calidad',
    message: '¿Seguro que quieres eliminar este control de calidad?',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await calidad.eliminarControlCalidad(control.id)
      $q.notify({ type: 'positive', message: 'Control de calidad eliminado.' })
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
  await calidad.cargarAlertas()
  if (produccion.parcelas.length > 0) {
    parcelaSeleccionada.value = produccion.parcelas[0].id
  }

  // Si se entro desde el menu con "Alertas" (que trae ?seccion=alertas),
  // se baja el scroll directo a esa seccion para que se note que si funciono.
  if (route.query.seccion === 'alertas') {
    setTimeout(() => {
      seccionAlertasRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 300)
  }
})
</script>

<style scoped>
.stat-card {
  background: linear-gradient(165deg, #fff6e6 0%, #f7e4bf 100%);
  border: 1px solid rgba(224, 168, 74, 0.4) !important;
  text-align: center;
}
.stat-icono {
  width: 2.625rem;
  height: 2.625rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.5rem;
  background: var(--gradiente-icono);
  box-shadow: 0 3px 8px rgba(138, 90, 30, 0.35);
}
.stat-numero {
  font-size: 1.625rem;
  font-weight: 700;
  color: #4a3a2a;
  line-height: 1.3;
  min-height: 2.125rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stat-numero--fecha {
  font-size: 1rem;
}
.stat-label {
  font-size: 0.75rem;
  color: #8a7458;
}

.foto-detalle-alerta {
  display: block;
  width: 100%;
  height: 180px;
  object-fit: cover;
  border: 1px solid rgba(184, 132, 42, 0.4);
}

.vista-previa-foto {
  display: block;
  max-width: 180px;
  width: 100%;
  height: 130px;
  object-fit: cover;
  border: 1px solid rgba(184, 132, 42, 0.4);
}

.foto-diagnostico {
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.foto-diagnostico:hover {
  transform: scale(1.08);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.alerta-no-leida {
  background: rgba(224, 168, 74, 0.12);
}

.visor-foto-card {
  background: rgba(20, 14, 8, 0.94);
  position: relative;
  width: 100%;
  height: 100%;
}
.visor-foto {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.foto-grande-img {
  max-width: 92vw;
  max-height: 90vh;
  object-fit: contain;
}
.visor-foto-cerrar {
  position: absolute;
  top: 12px;
  right: 12px;
}
</style>
