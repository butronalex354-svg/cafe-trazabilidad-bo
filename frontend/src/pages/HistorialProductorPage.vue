<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Historial de verificaciones</div>
    <div class="text-caption text-grey-7 q-mb-md">A qué resultado llegó cada solicitud de verificación de tus lotes</div>

    <q-inner-loading :showing="cargando" />

    <div v-if="!cargando && historial.length === 0" class="caja-tema rounded-borders q-pa-xl text-center">
      <q-icon name="history" size="48px" color="brown-6" class="q-mb-sm" />
      <div class="text-subtitle1 text-brown-9">Todavía no tenés verificaciones resueltas</div>
      <div class="text-caption text-grey-7">Cuando el Verificador resuelva una solicitud tuya, va a aparecer acá.</div>
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="h in historial" :key="h.id">
        <q-item-section avatar>
          <q-icon :name="iconoEstado(h.estado)" :color="colorEstado(h.estado)" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ h.cosecha?.codigo_trazabilidad }} · {{ h.cosecha?.variedad_cafe }}</q-item-label>
          <q-item-label caption>Verificado por: {{ h.verificador?.nombre }}</q-item-label>
          <q-item-label caption>{{ formatearFecha(h.fecha_resolucion) }}</q-item-label>
          <q-item-label v-if="h.observaciones" caption class="text-negative">{{ h.observaciones }}</q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-badge :color="colorEstado(h.estado)" :label="etiquetaEstado(h.estado)" />
        </q-item-section>
      </q-item>
    </q-list>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/boot/axios'

const historial = ref([])
const cargando = ref(true)

function etiquetaEstado (estado) {
  return { verificado: 'Verificado', observado: 'Observado', rechazado: 'Rechazado' }[estado] || estado
}
function iconoEstado (estado) {
  return { verificado: 'check_circle', observado: 'visibility', rechazado: 'cancel' }[estado] || 'help'
}
function colorEstado (estado) {
  return { verificado: 'positive', observado: 'orange-9', rechazado: 'negative' }[estado] || 'grey'
}
function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  cargando.value = true
  try {
    const { data } = await api.get('/productor/historial-verificaciones')
    historial.value = data
  } finally {
    cargando.value = false
  }
})
</script>
