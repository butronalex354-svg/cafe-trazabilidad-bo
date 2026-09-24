<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Inventario</div>
    <div class="text-caption text-grey-7 q-mb-md">Stock disponible por lote, según su estado</div>

    <div v-if="produccion.parcelas.length === 0 && !cargandoParcelas" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no tienes parcelas registradas. Ve a <b>Parcelas</b> primero para crear una.
    </div>

    <template v-else>
      <!-- Resumen -->
      <div class="row q-col-gutter-md q-mb-md items-stretch">
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="stat-card full-height">
            <q-card-section>
              <div class="stat-icono"><q-icon name="inventory_2" color="white" size="20px" /></div>
              <div class="stat-numero">{{ inventario.inventario.length }}</div>
              <div class="stat-label">Lotes totales</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="stat-card full-height">
            <q-card-section>
              <div class="stat-icono"><q-icon name="settings_suggest" color="white" size="20px" /></div>
              <div class="stat-numero">{{ contarPorEstado('en_proceso') }}</div>
              <div class="stat-label">En proceso</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="stat-card full-height">
            <q-card-section>
              <div class="stat-icono"><q-icon name="task_alt" color="white" size="20px" /></div>
              <div class="stat-numero">{{ contarPorEstado('terminado') }}</div>
              <div class="stat-label">Terminados</div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6 col-sm-3">
          <q-card flat bordered class="stat-card full-height">
            <q-card-section>
              <div class="stat-icono"><q-icon name="local_shipping" color="white" size="20px" /></div>
              <div class="stat-numero">{{ contarPorEstado('exportado') }}</div>
              <div class="stat-label">Exportados</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Filtros -->
      <div class="row q-col-gutter-sm q-mb-md">
        <div class="col-12 col-sm-6">
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
        <div class="col-12 col-sm-6">
          <q-select
            v-model="filtroEstado"
            :options="opcionesEstado"
            emit-value
            map-options
            label="Estado"
            dense
            outlined
            bg-color="white"
          />
        </div>
      </div>

      <q-inner-loading :showing="inventario.cargando" />

      <div v-if="!inventario.cargando && inventarioFiltrado.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
        No hay lotes que coincidan con el filtro.
      </div>

      <q-list v-else bordered separator class="rounded-borders caja-tema">
        <q-item v-for="i in inventarioFiltrado" :key="i.id">
          <q-item-section avatar>
            <q-icon :name="iconoEstado(i.estado)" :color="colorEstado(i.estado)" size="28px" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ i.cosecha.variedad_cafe }} · {{ i.cosecha.codigo_trazabilidad }}</q-item-label>
            <q-item-label caption>{{ i.cosecha.parcela?.nombre_parcela }} · {{ i.cantidad_disponible }} kg disponibles</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-select
              :model-value="i.estado"
              :options="opcionesEstadoFormulario"
              emit-value
              map-options
              dense
              outlined
              style="min-width: 150px"
              @update:model-value="(nuevoEstado) => cambiarEstado(i, nuevoEstado)"
            />
          </q-item-section>
        </q-item>
      </q-list>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useProduccionStore } from '@/stores/produccion'
import { useInventarioStore } from '@/stores/inventario'

const $q = useQuasar()
const produccion = useProduccionStore()
const inventario = useInventarioStore()

const cargandoParcelas = ref(true)
const filtroParcelaId = ref(null)
const filtroEstado = ref(null)

const opcionesParcela = computed(() => [
  { label: 'Todas las parcelas', value: null },
  ...produccion.parcelas.map(p => ({ label: p.nombre_parcela, value: p.id }))
])

const opcionesEstado = [
  { label: 'Todos los estados', value: null },
  { label: 'En proceso', value: 'en_proceso' },
  { label: 'Terminado', value: 'terminado' },
  { label: 'Exportado', value: 'exportado' }
]

const opcionesEstadoFormulario = [
  { label: 'En proceso', value: 'en_proceso' },
  { label: 'Terminado', value: 'terminado' },
  { label: 'Exportado', value: 'exportado' }
]

const inventarioFiltrado = computed(() => {
  return inventario.inventario.filter(i => {
    if (filtroParcelaId.value && i.cosecha.parcela_id !== filtroParcelaId.value) return false
    if (filtroEstado.value && i.estado !== filtroEstado.value) return false
    return true
  })
})

function contarPorEstado (estado) {
  return inventario.inventario.filter(i => i.estado === estado).length
}

function iconoEstado (estado) {
  return { en_proceso: 'settings_suggest', terminado: 'task_alt', exportado: 'local_shipping' }[estado] || 'help'
}

function colorEstado (estado) {
  return { en_proceso: 'amber-8', terminado: 'positive', exportado: 'brown-8' }[estado] || 'grey'
}

async function cambiarEstado (item, nuevoEstado) {
  if (nuevoEstado === item.estado) return
  try {
    await inventario.cambiarEstado(item.id, nuevoEstado)
    $q.notify({ type: 'positive', message: 'Estado del lote actualizado.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo actualizar el estado.' })
  }
}

onMounted(async () => {
  cargandoParcelas.value = true
  if (produccion.parcelas.length === 0) {
    await produccion.cargarParcelas()
  }
  cargandoParcelas.value = false
  await inventario.cargarInventario()
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
.stat-label {
  font-size: 0.75rem;
  color: #8a7458;
}
</style>
