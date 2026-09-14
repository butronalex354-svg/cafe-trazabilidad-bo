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
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <div>
              <q-btn flat dense round size="sm" icon="edit" color="brown-7" @click="abrirEditarCosecha(cosecha)" />
              <q-btn flat dense round size="sm" icon="delete" color="negative" @click="confirmarEliminarCosecha(cosecha)" />
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
          <q-input v-model="formCosecha.variedad_cafe" label="Variedad de café" dense outlined placeholder="Caturra, Typica, Catuaí..." />
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
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useProduccionStore } from '@/stores/produccion'

const $q = useQuasar()
const route = useRoute()
const produccion = useProduccionStore()

const guardando = ref(false)

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

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

onMounted(async () => {
  await Promise.all([produccion.cargarParcelas(), produccion.cargarCosechas()])
})
</script>

<style scoped>
/* El fondo con degradado ahora lo pone la clase compartida "pagina-tema"
   (app.scss), asi se ve igual en Inicio, Parcelas y Producción. */
</style>
