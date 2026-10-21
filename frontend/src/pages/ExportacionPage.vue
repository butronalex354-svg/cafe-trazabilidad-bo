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
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
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
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo autorizar la exportación.' })
    }
  })
}

onMounted(() => {
  admin.cargarLotesParaExportar()
})
</script>
