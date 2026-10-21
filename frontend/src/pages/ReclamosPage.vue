<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-brown-9 q-mb-xs">Reclamos</div>
        <div class="text-caption text-grey-7">Reclamos registrados por compradores desde la página pública del QR</div>
      </div>
      <q-select
        v-model="filtroEstado"
        :options="opcionesEstado"
        emit-value
        map-options
        dense
        outlined
        bg-color="white"
        style="min-width: 180px"
        @update:model-value="admin.cargarReclamos(filtroEstado)"
      />
    </div>

    <q-inner-loading :showing="admin.cargando" />

    <div v-if="!admin.cargando && admin.reclamos.length === 0" class="caja-tema rounded-borders q-pa-xl text-center">
      <q-icon name="support_agent" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">No hay reclamos</div>
      <div class="text-caption text-grey-7" style="max-width: 420px; margin: 0 auto">
        Cuando un comprador registre un reclamo desde la página pública de un lote, va a aparecer acá.
      </div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="r in admin.reclamos" :key="r.id">
        <q-item-section avatar>
          <q-icon :name="r.estado === 'atendido' ? 'check_circle' : 'report'" :color="r.estado === 'atendido' ? 'positive' : 'negative'" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ r.cosecha?.codigo_trazabilidad }} · {{ r.cosecha?.variedad_cafe }}</q-item-label>
          <q-item-label caption>{{ r.mensaje }}</q-item-label>
          <q-item-label caption class="text-grey-7">
            {{ r.nombre_comprador || 'Anónimo' }}
            <template v-if="r.contacto_comprador"> · {{ r.contacto_comprador }}</template>
            · {{ formatearFecha(r.created_at) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-badge v-if="r.estado === 'atendido'" color="positive" label="Atendido" />
          <q-btn v-else dense unelevated no-caps color="brown-8" label="Marcar atendido" @click="atender(r)" />
        </q-item-section>
      </q-item>
    </q-list>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useAdministradorStore } from '@/stores/administrador'

const $q = useQuasar()
const admin = useAdministradorStore()

const filtroEstado = ref(null)
const opcionesEstado = [
  { label: 'Todos', value: null },
  { label: 'Pendientes', value: 'pendiente' },
  { label: 'Atendidos', value: 'atendido' }
]

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function atender (r) {
  try {
    await admin.atenderReclamo(r.id)
    $q.notify({ type: 'positive', message: 'Reclamo marcado como atendido.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo actualizar el reclamo.' })
  }
}

onMounted(() => {
  admin.cargarReclamos()
})
</script>
