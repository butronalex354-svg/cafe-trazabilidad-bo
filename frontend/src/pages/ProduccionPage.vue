<template>
  <q-page class="q-pa-md produccion-page pagina-tema">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-brown-9">Producción</div>
        <div class="text-caption text-grey-7">Cosechas y lotes de todas tus parcelas</div>
      </div>
      <q-btn unelevated color="brown-8" icon="add" label="Registrar cosecha" @click="abrirNuevaCosecha" />
    </div>

    <div v-if="produccion.parcelas.length === 0 && !produccion.cargando" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no tienes parcelas registradas. Ve a <b>Parcelas</b> primero para crear una, y después podrás registrar cosechas aquí.
    </div>

    <template v-else>
      <div class="row q-col-gutter-sm q-mb-md">
        <div class="col-12 col-sm-4">
          <q-select
            v-model="filtroParcelaId"
            :options="opcionesParcela"
            emit-value
            map-options
            label="Parcela"
            dense
            outlined
            bg-color="white"
          />
        </div>
        <div class="col-6 col-sm-4">
          <q-input v-model="filtroDesde" label="Desde" dense outlined readonly bg-color="white">
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="filtroDesde" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
        <div class="col-6 col-sm-4">
          <q-input v-model="filtroHasta" label="Hasta" dense outlined readonly bg-color="white">
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="filtroHasta" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
        </div>
      </div>

      <div v-if="filtroParcelaId && tendenciaRendimiento.length >= 2" class="q-mb-md">
        <div class="text-h6 text-brown-8 q-mb-sm">Tendencia de rendimiento (kg/ha)</div>
        <q-card flat bordered class="caja-tema q-pa-md">
          <div class="fila-tendencia">
            <div v-for="punto in tendenciaRendimiento" :key="punto.id" class="barra-tendencia-col">
              <div class="barra-tendencia-valor">{{ punto.rendimiento_kg_ha }}</div>
              <div class="barra-tendencia-pista">
                <div
                  class="barra-tendencia"
                  :style="{ height: alturaBarra(punto.rendimiento_kg_ha) + '%' }"
                  :class="{ 'barra-tendencia--max': Number(punto.rendimiento_kg_ha) === maxRendimiento }"
                />
              </div>
              <div class="text-caption text-grey-7 q-mt-xs">{{ formatearFecha(punto.fecha) }}</div>
            </div>
          </div>
          <div class="text-caption text-grey-7 q-mt-sm">
            Rendimiento = cantidad cosechada (kg) ÷ tamaño de la parcela (ha), calculado automáticamente por el sistema en cada cosecha.
          </div>
        </q-card>
      </div>

      <q-inner-loading :showing="produccion.cargando" />

      <div v-if="!produccion.cargando && cosechasFiltradas.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
        No hay cosechas que coincidan con el filtro. Usa "Registrar cosecha" para agregar una.
      </div>

      <q-list v-else bordered separator class="rounded-borders caja-tema">
        <q-item v-for="cosecha in cosechasFiltradas" :key="cosecha.id">
          <q-item-section avatar>
            <q-icon name="agriculture" color="brown-7" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold">
              {{ cosecha.parcela?.nombre_parcela }}
            </q-item-label>
            <q-item-label caption>
              {{ cosecha.variedad_cafe }} — {{ cosecha.cantidad }} kg · {{ formatearFecha(cosecha.fecha) }}
            </q-item-label>
            <q-item-label caption>
              <q-badge color="brown-6">{{ cosecha.codigo_trazabilidad }}</q-badge>
              <q-badge v-if="cosecha.verificacion" :color="colorVerificacion(cosecha.verificacion.estado)" class="q-ml-xs">
                {{ etiquetaVerificacion(cosecha.verificacion.estado) }}
              </q-badge>
            </q-item-label>
            <q-item-label v-if="cosecha.verificacion?.estado === 'observado' && cosecha.verificacion?.observaciones" caption class="text-negative">
              Observación del verificador: {{ cosecha.verificacion.observaciones }}
            </q-item-label>
            <q-item-label v-else-if="cosecha.verificacion?.estado === 'rechazado'" caption class="text-negative">
              Este lote fue rechazado y no se puede volver a enviar a verificación.
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="column items-end q-gutter-xs">
              <div>
                <q-btn flat dense round size="sm" icon="qr_code_2" color="brown-7" @click="abrirQr(cosecha)" />
                <q-btn flat dense round size="sm" icon="edit" color="brown-7" @click="abrirEditarCosecha(cosecha)" />
                <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarCosecha(cosecha)" />
              </div>
              <q-btn
                v-if="!cosecha.verificacion || cosecha.verificacion.estado === 'observado'"
                dense
                outline
                no-caps
                size="sm"
                color="brown-8"
                icon="verified"
                label="Solicitar verificación"
                @click="solicitarVerificacion(cosecha)"
              />
            </div>
          </q-item-section>
        </q-item>
      </q-list>
    </template>

    <!-- Dialogo: cosecha (nueva / editar) -->
    <q-dialog v-model="dlgCosecha">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">{{ cosechaEditando ? 'Editar cosecha' : 'Registrar cosecha' }}</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-select
            v-if="!cosechaEditando"
            v-model="formCosecha.parcela_id"
            :options="opcionesParcelaFormulario"
            emit-value
            map-options
            label="Parcela"
            dense
            outlined
            :rules="[val => !!val || 'Elige una parcela']"
          />
          <div v-else class="text-caption text-grey-7">
            Parcela: <b>{{ cosechaEditando.parcela?.nombre_parcela }}</b> (no se puede cambiar)
          </div>

          <q-input v-model="formCosecha.fecha" label="Fecha" dense outlined readonly>
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                  <q-date v-model="formCosecha.fecha" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                    <div class="row items-center justify-end">
                      <q-btn v-close-popup label="Cerrar" color="brown-8" flat />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input v-model.number="formCosecha.cantidad" type="number" step="0.01" label="Cantidad (kg)" dense outlined />
          <q-select
            v-model="formCosecha.variedad_cafe"
            label="Variedad de café"
            dense
            outlined
            use-input
            new-value-mode="add-unique"
            :options="opcionesVariedad"
          />
          <div v-if="cosechaEditando" class="text-caption text-grey-7">
            Código de trazabilidad: <b>{{ cosechaEditando.codigo_trazabilidad }}</b> (no se puede editar)
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardando" @click="guardarCosecha" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo: codigo QR del lote (RF18), para que el comprador lo escanee -->
    <q-dialog v-model="dlgQr">
      <q-card class="caja-tema text-center" style="min-width: 300px; max-width: 360px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">{{ cosechaQr?.codigo_trazabilidad }}</div>
          <div class="text-caption text-grey-7">Código QR de trazabilidad pública</div>
        </q-card-section>
        <q-card-section>
          <img v-if="qrDataUrl" :src="qrDataUrl" alt="Código QR" style="width: 220px; height: 220px" />
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-input :model-value="enlaceQr" readonly dense outlined bg-color="white">
            <template v-slot:append>
              <q-btn flat dense round icon="content_copy" color="brown-7" @click="copiarEnlace" />
            </template>
          </q-input>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useProduccionStore } from '@/stores/produccion'
import { api } from '@/boot/axios'

const $q = useQuasar()
const route = useRoute()
const produccion = useProduccionStore()

const guardando = ref(false)
const opcionesVariedad = ref([])

// Si venimos de "Ver lotes" en Parcelas (?parcela=ID), arrancamos filtrados a esa.
const filtroParcelaId = ref(route.query.parcela ? Number(route.query.parcela) : null)
const filtroDesde = ref('')
const filtroHasta = ref('')

const opcionesParcela = computed(() => [
  { label: 'Todas las parcelas', value: null },
  ...produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
])

const opcionesParcelaFormulario = computed(() =>
  produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
)

const cosechasFiltradas = computed(() => {
  return produccion.cosechas
    .filter(c => !filtroParcelaId.value || c.parcela_id === filtroParcelaId.value)
    .filter(c => !filtroDesde.value || c.fecha >= filtroDesde.value)
    .filter(c => !filtroHasta.value || c.fecha <= filtroHasta.value)
})

// --- Tendencia de rendimiento (kg/ha) de la parcela filtrada ---
const tendenciaRendimiento = computed(() => {
  return cosechasFiltradas.value
    .filter(c => c.rendimiento_kg_ha != null)
    .slice()
    .sort((a, b) => (a.fecha > b.fecha ? 1 : -1))
})

const maxRendimiento = computed(() => {
  if (tendenciaRendimiento.value.length === 0) return 0
  return Math.max(...tendenciaRendimiento.value.map(c => Number(c.rendimiento_kg_ha)))
})

function alturaBarra (valor) {
  if (!maxRendimiento.value) return 0
  return Math.max(6, Math.round((Number(valor) / maxRendimiento.value) * 100))
}

// --- Cosechas ---
const dlgCosecha = ref(false)
const cosechaEditando = ref(null)
const formCosecha = ref({ parcela_id: null, fecha: '', cantidad: null, variedad_cafe: '' })

function abrirNuevaCosecha () {
  cosechaEditando.value = null
  formCosecha.value = { parcela_id: filtroParcelaId.value, fecha: '', cantidad: null, variedad_cafe: '' }
  dlgCosecha.value = true
}

function abrirEditarCosecha (cosecha) {
  cosechaEditando.value = cosecha
  formCosecha.value = { parcela_id: cosecha.parcela_id, fecha: cosecha.fecha, cantidad: cosecha.cantidad, variedad_cafe: cosecha.variedad_cafe }
  dlgCosecha.value = true
}

async function guardarCosecha () {
  if (!cosechaEditando.value && !formCosecha.value.parcela_id) {
    $q.notify({ type: 'negative', message: 'Elige una parcela para la cosecha.' })
    return
  }
  guardando.value = true
  try {
    if (cosechaEditando.value) {
      await produccion.editarCosecha(cosechaEditando.value.id, formCosecha.value)
    } else {
      await produccion.crearCosecha(formCosecha.value)
    }
    $q.notify({ type: 'positive', message: 'Cosecha guardada.' })
    dlgCosecha.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar la cosecha.' })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminarCosecha (cosecha) {
  $q.dialog({
    title: 'Eliminar cosecha',
    message: `¿Seguro que quieres eliminar el lote "${cosecha.codigo_trazabilidad}"?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await produccion.eliminarCosecha(cosecha.id)
      $q.notify({ type: 'positive', message: 'Cosecha eliminada.' })
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo eliminar.' })
    }
  })
}

function etiquetaVerificacion (estado) {
  return { pendiente: 'En revisión', observado: 'Observado', verificado: 'Verificado', rechazado: 'Rechazado' }[estado] || estado
}

function colorVerificacion (estado) {
  return { pendiente: 'amber-8', observado: 'orange-9', verificado: 'positive', rechazado: 'negative' }[estado] || 'grey'
}

async function solicitarVerificacion (cosecha) {
  try {
    await produccion.solicitarVerificacion(cosecha.id)
    $q.notify({ type: 'positive', message: 'Solicitud de verificación enviada.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo solicitar la verificación.' })
  }
}

// --- Codigo QR del lote (RF18) ---
const dlgQr = ref(false)
const cosechaQr = ref(null)
const qrDataUrl = ref('')
const enlaceQr = computed(() => cosechaQr.value
  ? `${window.location.origin}${window.location.pathname}#/trazabilidad/${cosechaQr.value.codigo_trazabilidad}`
  : '')

async function abrirQr (cosecha) {
  cosechaQr.value = cosecha
  dlgQr.value = true
  const QRCode = (await import('qrcode')).default
  qrDataUrl.value = await QRCode.toDataURL(enlaceQr.value, { width: 220, margin: 1 })
}

function copiarEnlace () {
  navigator.clipboard.writeText(enlaceQr.value)
  $q.notify({ type: 'positive', message: 'Enlace copiado.' })
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

onMounted(async () => {
  await Promise.all([produccion.cargarParcelas(), produccion.cargarCosechas()])
  try {
    const { data } = await api.get('/catalogos/variedades-cafe')
    opcionesVariedad.value = data.map(v => v.nombre)
  } catch {
    // si falla, el selector queda vacio pero se puede igual escribir una variedad nueva
  }
})
</script>

<style scoped>
/* El fondo con degradado ahora lo pone la clase compartida "pagina-tema"
   (app.scss), asi se ve igual en Inicio, Parcelas y Producción. */

/* Grafica de barras de la tendencia de rendimiento, hecha a mano con CSS
   (sin agregar una libreria de graficos nueva al proyecto). */
.fila-tendencia {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: center;
  gap: 14px;
}
.barra-tendencia-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 0 0 auto;
  width: 56px;
}
.barra-tendencia-valor {
  font-size: 12px;
  color: #5d4037;
  font-weight: bold;
  margin-bottom: 2px;
}
.barra-tendencia-pista {
  width: 28px;
  height: 120px;
  display: flex;
  align-items: flex-end;
  background: rgba(93, 64, 55, 0.08);
  border-radius: 4px;
  overflow: hidden;
}
.barra-tendencia {
  width: 100%;
  background: linear-gradient(180deg, #d7a85e 0%, #a9712b 100%);
  border-radius: 4px 4px 0 0;
  transition: height 0.25s ease;
}
.barra-tendencia--max {
  background: linear-gradient(180deg, #7cb342 0%, #4e7a26 100%);
}
</style>
