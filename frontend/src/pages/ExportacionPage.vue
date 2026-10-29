<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Exportación</div>
    <div class="text-caption text-grey-7 q-mb-md">Autorizá la exportación final de los lotes ya verificados</div>

    <q-inner-loading :showing="admin.cargando" />

    <div v-if="!admin.cargando && admin.lotesParaExportar.length === 0" class="caja-tema rounded-borders q-pa-xl text-center">
      <q-icon name="local_shipping" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">No hay lotes esperando autorización</div>
      <div class="text-caption text-grey-7" style="max-width: 420px; margin: 0 auto">
        Acá van a aparecer los lotes que el Verificador ya marcó como "Verificado" y todavía no fueron exportados.
      </div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="c in admin.lotesParaExportar" :key="c.id">
        <q-item-section avatar>
          <q-icon name="local_cafe" color="brown-7" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ c.codigo_trazabilidad }} · {{ c.variedad_cafe }} · {{ c.cantidad }} kg</q-item-label>
          <q-item-label caption>
            {{ c.parcela?.productor?.nombre_completo }} · {{ c.parcela?.nombre_parcela }}
          </q-item-label>
          <q-item-label caption>
            <q-badge color="positive" label="Verificado" />
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-btn unelevated no-caps color="brown-8" icon="task_alt" label="Autorizar exportación" @click="autorizar(c)" />
        </q-item-section>
      </q-item>
    </q-list>

    <div class="text-h5 text-brown-9 q-mb-xs q-mt-xl">Seguimiento de envío</div>
    <div class="text-caption text-grey-7 q-mb-md">Actualizá el estado del transporte de cada lote ya exportado, hasta que llegue al comprador</div>

    <div v-if="!admin.cargando && admin.lotesExportados.length === 0" class="caja-tema rounded-borders q-pa-xl text-center">
      <q-icon name="flight_takeoff" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">Todavía no hay lotes exportados</div>
      <div class="text-caption text-grey-7">Acá vas a poder ir cargando el seguimiento una vez que autorices alguna exportación.</div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-expansion-item v-for="c in admin.lotesExportados" :key="c.id" expand-separator>
        <template #header>
          <q-item-section avatar>
            <q-icon :name="iconoEstado(ultimoEstado(c))" :color="colorEstado(ultimoEstado(c))" size="28px" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ c.codigo_trazabilidad }} · {{ c.variedad_cafe }}</q-item-label>
            <q-item-label caption>{{ c.parcela?.productor?.nombre_completo }} · {{ c.parcela?.nombre_parcela }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-badge :color="colorEstado(ultimoEstado(c))" :label="etiquetaEstado(ultimoEstado(c)) || 'Sin envío cargado'" />
          </q-item-section>
        </template>

        <q-card flat class="caja-tema">
          <q-card-section>
            <div v-if="c.envios.length === 0" class="text-caption text-grey-7 q-mb-sm">
              Este lote todavía no tiene ningún estado de envío cargado.
            </div>
            <q-timeline v-else color="brown-7" class="q-mb-sm">
              <q-timeline-entry
                v-for="e in c.envios"
                :key="e.id"
                :title="etiquetaEstado(e.estado)"
                :subtitle="formatearFecha(e.fecha)"
                :icon="iconoEstado(e.estado)"
                :color="colorEstado(e.estado)"
              >
                <div v-if="e.nota">{{ e.nota }}</div>
              </q-timeline-entry>
            </q-timeline>
            <q-btn
              unelevated no-caps color="brown-8" icon="add" label="Agregar estado de envío"
              :disable="ultimoEstado(c) === 'entregado'"
              @click="abrirDialogoEnvio(c)"
            />
          </q-card-section>
        </q-card>
      </q-expansion-item>
    </q-list>

    <!-- Dialogo para agregar un estado de envio -->
    <q-dialog v-model="dlgEnvio">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 420px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Agregar estado de envío</div>
          <div class="text-caption text-grey-7">Lote {{ loteSeleccionado?.codigo_trazabilidad }}</div>
        </q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-select
            v-model="formEnvio.estado"
            :options="opcionesEstado"
            emit-value map-options
            label="Nuevo estado"
            dense outlined bg-color="white"
          />
          <q-input v-model="formEnvio.fecha" label="Fecha" type="date" dense outlined bg-color="white" />
          <q-input v-model="formEnvio.nota" label="Nota (opcional)" type="textarea" rows="2" dense outlined bg-color="white" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" no-caps :loading="guardandoEnvio" @click="guardarEnvio" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useAdministradorStore } from '@/stores/administrador'

const $q = useQuasar()
const admin = useAdministradorStore()

async function autorizar (c) {
  $q.dialog({
    title: 'Autorizar exportación',
    message: `¿Confirmás autorizar la exportación final del lote "${c.codigo_trazabilidad}"? Esta acción marca el lote como exportado.`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await admin.autorizarExportacion(c.id)
      $q.notify({ type: 'positive', message: 'Exportación autorizada.' })
      admin.cargarLotesExportados()
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo autorizar la exportación.' })
    }
  })
}

const etiquetasEstado = { listo_transporte: 'Listo para transporte', en_transito: 'En tránsito', en_aduana: 'En aduana', entregado: 'Entregado al comprador' }
const coloresEstado = { listo_transporte: 'grey-7', en_transito: 'blue-7', en_aduana: 'orange-8', entregado: 'positive' }
const iconosEstado = { listo_transporte: 'inventory_2', en_transito: 'local_shipping', en_aduana: 'fact_check', entregado: 'check_circle' }
const opcionesEstado = Object.entries(etiquetasEstado).map(([value, label]) => ({ value, label }))

function ultimoEstado (c) {
  return c.envios?.length ? c.envios[c.envios.length - 1].estado : null
}
function etiquetaEstado (estado) {
  return etiquetasEstado[estado] || ''
}
function colorEstado (estado) {
  return coloresEstado[estado] || 'grey-6'
}
function iconoEstado (estado) {
  return iconosEstado[estado] || 'local_shipping'
}
function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

const dlgEnvio = ref(false)
const guardandoEnvio = ref(false)
const loteSeleccionado = ref(null)
const formEnvio = ref({ estado: 'listo_transporte', fecha: new Date().toISOString().slice(0, 10), nota: '' })

function abrirDialogoEnvio (c) {
  loteSeleccionado.value = c
  const siguiente = { null: 'listo_transporte', listo_transporte: 'en_transito', en_transito: 'en_aduana', en_aduana: 'entregado' }
  formEnvio.value = { estado: siguiente[ultimoEstado(c)] || 'listo_transporte', fecha: new Date().toISOString().slice(0, 10), nota: '' }
  dlgEnvio.value = true
}

async function guardarEnvio () {
  guardandoEnvio.value = true
  try {
    await admin.agregarEstadoEnvio(loteSeleccionado.value.id, formEnvio.value)
    $q.notify({ type: 'positive', message: 'Estado de envío agregado.' })
    dlgEnvio.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo agregar el estado.' })
  } finally {
    guardandoEnvio.value = false
  }
}

onMounted(() => {
  admin.cargarLotesParaExportar()
  admin.cargarLotesExportados()
})
</script>
