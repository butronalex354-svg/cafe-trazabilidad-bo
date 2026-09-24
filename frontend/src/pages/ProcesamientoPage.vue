<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Procesamiento</div>
    <div class="text-caption text-grey-7 q-mb-md">Etapas post-cosecha y línea de tiempo del lote</div>

    <div v-if="produccion.parcelas.length === 0 && !cargandoParcelas" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no tienes parcelas registradas. Ve a <b>Parcelas</b> primero para crear una.
    </div>

    <template v-else>
      <div class="row q-col-gutter-sm q-mb-md">
        <div class="col-12 col-sm-6">
          <q-select
            v-model="parcelaSeleccionada"
            :options="opcionesParcela"
            emit-value
            map-options
            label="Parcela"
            dense
            outlined
            bg-color="white"
          />
        </div>
        <div class="col-12 col-sm-6">
          <q-select
            v-model="loteSeleccionado"
            :options="opcionesLote"
            emit-value
            map-options
            label="Lote (cosecha)"
            dense
            outlined
            bg-color="white"
            :disable="opcionesLote.length === 0"
            :hint="opcionesLote.length === 0 && parcelaSeleccionada ? 'Esta parcela todavía no tiene lotes registrados' : ''"
          />
        </div>
      </div>

      <template v-if="loteSeleccionado">
        <div class="row items-center justify-between q-mb-sm">
          <div class="text-h6 text-brown-8">Línea de tiempo del lote</div>
          <q-btn
            unelevated
            color="brown-8"
            icon="add"
            label="Registrar etapa"
            :disable="etapasPendientes.length === 0"
            @click="abrirNuevaEtapa"
          />
        </div>

        <q-inner-loading :showing="procesamiento.cargandoEtapas" />

        <q-timeline color="brown-8" class="caja-tema q-pa-md rounded-borders">
          <q-timeline-entry
            v-for="etapa in lineaDeTiempo"
            :key="etapa.clave"
            :title="etapa.etiqueta"
            :subtitle="etapa.completada ? `${formatearFecha(etapa.datos.fecha)} · ${etapa.datos.responsable}` : 'Pendiente'"
            :icon="etapa.icono"
            :color="etapa.completada ? 'brown-8' : 'grey-5'"
          >
            <div v-if="etapa.completada">
              <div v-if="etapa.datos.observaciones" class="text-caption text-grey-7 q-mb-xs">{{ etapa.datos.observaciones }}</div>
              <q-btn flat dense size="sm" icon="delete" color="negative" label="Eliminar" @click="confirmarEliminarEtapa(etapa.datos)" />
            </div>
            <div v-else class="text-caption text-grey-6">Todavía no se registró esta etapa.</div>
          </q-timeline-entry>
        </q-timeline>
      </template>
    </template>

    <!-- Dialogo: registrar etapa -->
    <q-dialog v-model="dlgEtapa">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">Registrar etapa</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-select
            v-model="formEtapa.etapa"
            :options="opcionesEtapaPendiente"
            emit-value
            map-options
            label="Etapa"
            dense
            outlined
          />
          <q-input v-model="formEtapa.fecha" label="Fecha" dense outlined readonly>
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="formEtapa.fecha" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input v-model="formEtapa.responsable" label="Responsable" dense outlined placeholder="¿Quién hizo esta etapa?" />
          <q-input v-model="formEtapa.observaciones" label="Observaciones (opcional)" type="textarea" dense outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardandoEtapa" @click="guardarEtapa" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useProduccionStore } from '@/stores/produccion'
import { useProcesamientoStore } from '@/stores/procesamiento'

const $q = useQuasar()
const produccion = useProduccionStore()
const procesamiento = useProcesamientoStore()

const cargandoParcelas = ref(true)
const parcelaSeleccionada = ref(null)
const loteSeleccionado = ref(null)

// Mismo orden y mismas claves que el backend (EtapaProcesamiento::ORDEN_ETAPAS).
const ETAPAS = [
  { clave: 'despulpado', etiqueta: 'Despulpado', icono: 'grain' },
  { clave: 'fermentacion', etiqueta: 'Fermentación', icono: 'science' },
  { clave: 'lavado', etiqueta: 'Lavado', icono: 'water_drop' },
  { clave: 'secado', etiqueta: 'Secado', icono: 'wb_sunny' },
  { clave: 'tostado', etiqueta: 'Tostado', icono: 'local_fire_department' },
  { clave: 'envasado', etiqueta: 'Envasado', icono: 'inventory_2' },
  { clave: 'embalaje', etiqueta: 'Embalaje', icono: 'inventory' }
]

const opcionesParcela = computed(() =>
  produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
)

const cosechasDeParcela = computed(() =>
  produccion.cosechas.filter(c => c.parcela_id === parcelaSeleccionada.value)
)

const opcionesLote = computed(() =>
  cosechasDeParcela.value.map(c => ({
    label: `${c.variedad_cafe} · ${c.codigo_trazabilidad}`,
    value: c.id
  }))
)

// Arma la linea de tiempo completa (las 7 etapas siempre visibles, en orden),
// marcando cuales ya se registraron y cuales siguen pendientes (RF12).
const lineaDeTiempo = computed(() =>
  ETAPAS.map(e => {
    const datos = procesamiento.etapas.find(x => x.etapa === e.clave)
    return { ...e, completada: !!datos, datos }
  })
)

const etapasPendientes = computed(() => lineaDeTiempo.value.filter(e => !e.completada))
const opcionesEtapaPendiente = computed(() =>
  etapasPendientes.value.map(e => ({ label: e.etiqueta, value: e.clave }))
)

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

async function cargarLotesDeParcela (parcelaId) {
  if (!parcelaId) return
  if (produccion.cosechas.filter(c => c.parcela_id === parcelaId).length === 0) {
    await produccion.cargarCosechas(parcelaId)
  }
  loteSeleccionado.value = null
}

watch(parcelaSeleccionada, (id) => {
  cargarLotesDeParcela(id)
})

watch(loteSeleccionado, (id) => {
  if (id) procesamiento.cargarEtapas(id)
})

// --- Registrar etapa ---
const dlgEtapa = ref(false)
const guardandoEtapa = ref(false)
const formEtapa = ref({ etapa: null, fecha: '', responsable: '', observaciones: '' })

function abrirNuevaEtapa () {
  formEtapa.value = { etapa: etapasPendientes.value[0]?.clave || null, fecha: '', responsable: '', observaciones: '' }
  dlgEtapa.value = true
}

async function guardarEtapa () {
  if (!formEtapa.value.etapa || !formEtapa.value.fecha || !formEtapa.value.responsable) {
    $q.notify({ type: 'negative', message: 'Completa la etapa, la fecha y el responsable.' })
    return
  }
  guardandoEtapa.value = true
  try {
    await procesamiento.crearEtapa({
      cosecha_id: loteSeleccionado.value,
      ...formEtapa.value
    })
    $q.notify({ type: 'positive', message: 'Etapa registrada.' })
    dlgEtapa.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo registrar la etapa.' })
  } finally {
    guardandoEtapa.value = false
  }
}

function confirmarEliminarEtapa (etapa) {
  $q.dialog({
    title: 'Eliminar etapa',
    message: `¿Seguro que quieres eliminar el registro de "${etapa.etapa}"?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await procesamiento.eliminarEtapa(etapa.id)
      $q.notify({ type: 'positive', message: 'Etapa eliminada.' })
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
