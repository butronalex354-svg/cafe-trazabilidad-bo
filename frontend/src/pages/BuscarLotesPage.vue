<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Buscar lotes</div>
    <div class="text-caption text-grey-7 q-mb-md">Búsqueda y filtrado general de lotes por productor, parcela, fecha o estado (RF26)</div>

    <div class="row q-col-gutter-sm q-mb-md">
      <div class="col-12 col-sm-4">
        <q-input
          v-model="filtros.q"
          label="Buscar por código, variedad, parcela o productor"
          dense
          outlined
          bg-color="white"
          clearable
          @keyup.enter="buscar"
        >
          <template v-slot:append><q-icon name="search" /></template>
        </q-input>
      </div>
      <div class="col-6 col-sm-3">
        <q-select
          v-model="filtros.estado_verificacion"
          :options="opcionesVerificacion"
          emit-value
          map-options
          label="Verificación"
          dense
          outlined
          bg-color="white"
        />
      </div>
      <div class="col-6 col-sm-2">
        <q-select
          v-model="filtros.estado_inventario"
          :options="opcionesInventario"
          emit-value
          map-options
          label="Inventario"
          dense
          outlined
          bg-color="white"
        />
      </div>
      <div class="col-6 col-sm-3">
        <q-input v-model="filtros.desde" label="Desde" dense outlined readonly bg-color="white">
          <template v-slot:append>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="filtros.desde" mask="YYYY-MM-DD" color="brown-8" today-btn minimal>
                  <div class="row items-center justify-end"><q-btn v-close-popup label="Cerrar" color="brown-8" flat /></div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </div>
    </div>

    <div class="row q-mb-md">
      <q-btn unelevated no-caps color="brown-8" icon="search" label="Buscar" @click="buscar" />
      <q-btn flat no-caps color="grey-8" label="Limpiar filtros" class="q-ml-sm" @click="limpiar" />
    </div>

    <q-inner-loading :showing="admin.cargando" />

    <div v-if="!admin.cargando && admin.resultadosBusqueda.length === 0" class="caja-tema rounded-borders q-pa-xl text-center">
      <q-icon name="travel_explore" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">Sin resultados</div>
      <div class="text-caption text-grey-7">Probá cambiando los filtros o el texto de búsqueda.</div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="l in admin.resultadosBusqueda" :key="l.id" clickable @click="abrirDetalle(l)">
        <q-item-section avatar>
          <q-icon name="local_cafe" color="brown-7" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ l.codigo_trazabilidad }} · {{ l.variedad_cafe }} · {{ l.cantidad }} kg</q-item-label>
          <q-item-label caption>
            {{ l.parcela?.productor?.nombre_completo }} · {{ l.parcela?.nombre_parcela }} · {{ formatearFecha(l.fecha) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="column items-end q-gutter-xs">
            <div class="row q-gutter-xs">
              <q-chip v-if="l.verificacion" dense square :color="colorVerificacion(l.verificacion.estado)" text-color="white" :icon="iconoVerificacion(l.verificacion.estado)" :label="etiquetaVerificacion(l.verificacion.estado)" />
              <q-chip dense square color="brown-6" text-color="white" :icon="iconoInventario(l.inventario?.estado)" :label="etiquetaInventario(l.inventario?.estado)" />
            </div>
            <q-btn flat dense no-caps size="sm" icon="visibility" label="Ver detalle" color="brown-8" @click.stop="abrirDetalle(l)" />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Dialogo de detalle (solo lectura, supervision general) -->
    <q-dialog v-model="dlgDetalle">
      <q-card v-if="loteDetalle" class="caja-tema" style="min-width: 320px; max-width: 560px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">{{ loteDetalle.codigo_trazabilidad }}</div>
          <div class="text-caption text-grey-7">
            {{ loteDetalle.parcela?.productor?.nombre_completo }} · {{ loteDetalle.parcela?.nombre_parcela }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="loteDetalle.parcela?.ubicacion">
          <div class="text-subtitle2 text-brown-8 q-mb-xs">Ubicación de la parcela</div>
          <ParcelaMapView :ubicacion="loteDetalle.parcela.ubicacion" :etiqueta="loteDetalle.parcela.nombre_parcela" :height="160" fill />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="text-subtitle2 text-brown-8 q-mb-xs">Procesamiento</div>
          <div v-if="!loteDetalle.etapas_procesamiento?.length" class="text-grey-7 text-caption">Sin etapas registradas.</div>
          <q-list v-else dense>
            <q-item v-for="e in loteDetalle.etapas_procesamiento" :key="e.id">
              <q-item-section>{{ e.etapa }} · {{ formatearFecha(e.fecha) }} · {{ e.responsable }}</q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="text-subtitle2 text-brown-8 q-mb-xs">Calidad</div>
          <div v-if="!loteDetalle.controles_calidad?.length" class="text-grey-7 text-caption">Sin controles de calidad registrados.</div>
          <q-list v-else dense>
            <q-item v-for="c in loteDetalle.controles_calidad" :key="c.id">
              <q-item-section>Humedad {{ c.humedad }}% · {{ c.defectos }} defectos · Clasificación {{ c.clasificacion }}</q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-separator v-if="loteDetalle.verificacion?.observaciones" />

        <q-card-section v-if="loteDetalle.verificacion?.observaciones">
          <div class="text-subtitle2 text-brown-8 q-mb-xs">Observaciones del verificador</div>
          <div class="text-body2">{{ loteDetalle.verificacion.observaciones }}</div>
        </q-card-section>

        <q-card-actions align="right" class="q-pb-md q-px-md">
          <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAdministradorStore } from '@/stores/administrador'
import ParcelaMapView from '@/components/ParcelaMapView.vue'

const admin = useAdministradorStore()

const filtros = ref({ q: '', estado_verificacion: null, estado_inventario: null, desde: '' })

const opcionesVerificacion = [
  { label: 'Cualquier verificación', value: null },
  { label: 'Pendiente', value: 'pendiente' },
  { label: 'Observado', value: 'observado' },
  { label: 'Verificado', value: 'verificado' },
  { label: 'Rechazado', value: 'rechazado' }
]
const opcionesInventario = [
  { label: 'Cualquiera', value: null },
  { label: 'En proceso', value: 'en_proceso' },
  { label: 'Terminado', value: 'terminado' },
  { label: 'Exportado', value: 'exportado' }
]

function etiquetaVerificacion (estado) {
  return { pendiente: 'En revisión', observado: 'Observado', verificado: 'Verificado', rechazado: 'Rechazado' }[estado] || estado
}
function colorVerificacion (estado) {
  return { pendiente: 'amber-8', observado: 'orange-9', verificado: 'positive', rechazado: 'negative' }[estado] || 'grey'
}
function iconoVerificacion (estado) {
  return { pendiente: 'schedule', observado: 'visibility', verificado: 'check_circle', rechazado: 'cancel' }[estado] || 'help'
}
function etiquetaInventario (estado) {
  return { en_proceso: 'En proceso', terminado: 'Terminado', exportado: 'Exportado' }[estado] || 'Sin inventario'
}
function iconoInventario (estado) {
  return { en_proceso: 'settings_suggest', terminado: 'task_alt', exportado: 'local_shipping' }[estado] || 'help'
}

const dlgDetalle = ref(false)
const loteDetalle = ref(null)

function abrirDetalle (lote) {
  loteDetalle.value = lote
  dlgDetalle.value = true
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

function buscar () {
  admin.buscarLotes({
    q: filtros.value.q || undefined,
    estado_verificacion: filtros.value.estado_verificacion || undefined,
    estado_inventario: filtros.value.estado_inventario || undefined,
    desde: filtros.value.desde || undefined
  })
}

function limpiar () {
  filtros.value = { q: '', estado_verificacion: null, estado_inventario: null, desde: '' }
  buscar()
}

onMounted(buscar)
</script>
