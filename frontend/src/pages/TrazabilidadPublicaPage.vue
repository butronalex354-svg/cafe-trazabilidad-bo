<template>
  <div class="pagina-publica">
    <div class="encabezado-publico">
      <img :src="logoCafeAndino" alt="Café Andino" class="logo-publico" />
      <div>
        <div class="text-h6 text-white">CaféTrace Bolivia</div>
        <div class="text-caption texto-suave">Verificación pública de origen y trazabilidad</div>
      </div>
    </div>

    <div class="contenido-publico">
      <q-inner-loading :showing="cargando" color="brown-8" />

      <div v-if="!cargando && error" class="caja-tema rounded-borders q-pa-xl text-center">
        <q-icon name="search_off" size="48px" color="brown-6" class="q-mb-sm" />
        <div class="text-subtitle1 text-brown-9">No encontramos ese lote</div>
        <div class="text-caption text-grey-7">Revisá que el código de trazabilidad sea correcto.</div>
      </div>

      <template v-else-if="!cargando && lote">
        <q-card flat bordered class="caja-tema q-pa-md q-mb-md">
          <div class="row items-center justify-between q-mb-sm">
            <div class="text-h6 text-brown-9">{{ lote.codigo_trazabilidad }}</div>
            <q-badge :color="colorVerificacion" :label="etiquetaVerificacion" class="q-pa-sm" />
          </div>
          <div class="text-body2">
            {{ lote.variedad_cafe }} · {{ lote.cantidad }} kg · Cosechado el {{ formatearFecha(lote.fecha) }}
          </div>
          <div class="text-caption text-grey-7 q-mt-xs">
            Productor: {{ lote.parcela?.productor?.nombre_completo }} · {{ lote.parcela?.nombre_parcela }}
          </div>

          <q-btn
            v-if="numeroWhatsapp"
            class="q-mt-md"
            unelevated
            no-caps
            color="positive"
            icon="chat"
            label="Contactar al productor por WhatsApp"
            type="a"
            :href="`https://wa.me/591${numeroWhatsapp}`"
            target="_blank"
          />
        </q-card>

        <q-card flat bordered class="caja-tema q-pa-md q-mb-md">
          <div class="text-subtitle2 text-brown-8 q-mb-sm">Origen (ubicación de la parcela)</div>
          <ParcelaMapView
            v-if="lote.parcela?.ubicacion"
            :ubicacion="lote.parcela.ubicacion"
            :etiqueta="lote.parcela.nombre_parcela"
            :height="180"
            fill
          />
          <div v-else class="text-grey-7 text-caption">Esta parcela no tiene ubicación GPS registrada.</div>
        </q-card>

        <q-card flat bordered class="caja-tema q-pa-md q-mb-md">
          <div class="text-subtitle2 text-brown-8 q-mb-sm">Procesamiento</div>
          <div v-if="lote.etapas_procesamiento?.length === 0" class="text-grey-7 text-caption">
            Todavía no se registraron etapas de procesamiento para este lote.
          </div>
          <q-timeline v-else color="brown-7">
            <q-timeline-entry
              v-for="e in lote.etapas_procesamiento"
              :key="e.id"
              :title="e.etapa"
              :subtitle="formatearFecha(e.fecha)"
            >
              <div v-if="e.responsable">Responsable: {{ e.responsable }}</div>
              <div v-if="e.observaciones">{{ e.observaciones }}</div>
            </q-timeline-entry>
          </q-timeline>
        </q-card>

        <q-card flat bordered class="caja-tema q-pa-md q-mb-md">
          <div class="text-subtitle2 text-brown-8 q-mb-sm">Calidad</div>
          <div v-if="lote.controles_calidad?.length === 0" class="text-grey-7 text-caption">
            Todavía no se registraron controles de calidad para este lote.
          </div>
          <q-list v-else dense>
            <q-item v-for="c in lote.controles_calidad" :key="c.id">
              <q-item-section>
                Humedad {{ c.humedad }}% · {{ c.defectos }} defectos · Clasificación: {{ c.clasificacion }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <div class="row q-col-gutter-sm q-mb-md">
          <div class="col-12 col-sm-6">
            <q-btn unelevated no-caps color="brown-8" icon="picture_as_pdf" label="Descargar certificado (PDF)" class="full-width" @click="descargarCertificado" />
          </div>
          <div class="col-12 col-sm-6">
            <q-btn outline no-caps color="brown-8" icon="report" label="Registrar un reclamo" class="full-width" @click="dlgReclamo = true" />
          </div>
        </div>
      </template>
    </div>

    <!-- Dialogo de reclamo -->
    <q-dialog v-model="dlgReclamo">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 420px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Registrar un reclamo</div>
          <div class="text-caption text-grey-7">Sobre el lote {{ lote?.codigo_trazabilidad }}</div>
        </q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="formReclamo.nombre_comprador" label="Tu nombre (opcional)" dense outlined bg-color="white" />
          <q-input v-model="formReclamo.contacto_comprador" label="Email o teléfono de contacto (opcional)" dense outlined bg-color="white" />
          <q-input v-model="formReclamo.mensaje" label="Describe el problema" type="textarea" rows="4" dense outlined bg-color="white" />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated color="brown-8" label="Enviar reclamo" no-caps :loading="enviandoReclamo" @click="enviarReclamo" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import ParcelaMapView from '@/components/ParcelaMapView.vue'
import logoCafeAndino from '@/assets/logo-cafe-andino-badge.png'

const route = useRoute()
const $q = useQuasar()

const cargando = ref(true)
const error = ref(false)
const lote = ref(null)

const numeroWhatsapp = computed(() => lote.value?.parcela?.productor?.whatsapp?.replace(/\D/g, '') || null)

const etiquetasVerificacion = { pendiente: 'En revisión', observado: 'Observado', verificado: 'Origen verificado', rechazado: 'Rechazado' }
const coloresVerificacion = { pendiente: 'amber-8', observado: 'orange-9', verificado: 'positive', rechazado: 'negative' }
const etiquetaVerificacion = computed(() => etiquetasVerificacion[lote.value?.verificacion?.estado] || 'Sin verificación todavía')
const colorVerificacion = computed(() => coloresVerificacion[lote.value?.verificacion?.estado] || 'grey-7')

function formatearFecha (fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO')
}

async function cargarLote () {
  cargando.value = true
  error.value = false
  try {
    const { data } = await api.get(`/trazabilidad/${route.params.codigo}`)
    lote.value = data
  } catch {
    error.value = true
  } finally {
    cargando.value = false
  }
}

async function descargarCertificado () {
  const { jsPDF } = await import('jspdf')
  const doc = new jsPDF()

  doc.setFontSize(16)
  doc.text('Certificado de Trazabilidad', 20, 20)
  doc.setFontSize(10)
  doc.text('CaféTrace Bolivia', 20, 27)

  doc.setFontSize(12)
  let y = 42
  const linea = (texto) => { doc.text(texto, 20, y); y += 8 }

  linea(`Código de trazabilidad: ${lote.value.codigo_trazabilidad}`)
  linea(`Variedad: ${lote.value.variedad_cafe}`)
  linea(`Cantidad: ${lote.value.cantidad} kg`)
  linea(`Fecha de cosecha: ${formatearFecha(lote.value.fecha)}`)
  linea(`Parcela: ${lote.value.parcela?.nombre_parcela}`)
  linea(`Productor: ${lote.value.parcela?.productor?.nombre_completo}`)
  linea(`Estado de verificación: ${etiquetaVerificacion.value}`)
  y += 4
  linea('Etapas de procesamiento registradas:')
  if (lote.value.etapas_procesamiento?.length) {
    lote.value.etapas_procesamiento.forEach(e => linea(`  - ${e.etapa} (${formatearFecha(e.fecha)})`))
  } else {
    linea('  Sin registros.')
  }
  y += 4
  linea('Controles de calidad:')
  if (lote.value.controles_calidad?.length) {
    lote.value.controles_calidad.forEach(c => linea(`  - Humedad ${c.humedad}%, ${c.defectos} defectos, clasificación ${c.clasificacion}`))
  } else {
    linea('  Sin registros.')
  }
  y += 10
  doc.setFontSize(9)
  doc.text(`Generado el ${new Date().toLocaleString('es-BO')} desde CaféTrace Bolivia.`, 20, y)

  doc.save(`certificado-${lote.value.codigo_trazabilidad}.pdf`)
}

const dlgReclamo = ref(false)
const enviandoReclamo = ref(false)
const formReclamo = ref({ nombre_comprador: '', contacto_comprador: '', mensaje: '' })

async function enviarReclamo () {
  if (!formReclamo.value.mensaje) {
    $q.notify({ type: 'negative', message: 'Describe el problema antes de enviar.' })
    return
  }
  enviandoReclamo.value = true
  try {
    await api.post(`/trazabilidad/${route.params.codigo}/reclamos`, formReclamo.value)
    $q.notify({ type: 'positive', message: 'Reclamo enviado. Gracias por avisarnos.' })
    dlgReclamo.value = false
    formReclamo.value = { nombre_comprador: '', contacto_comprador: '', mensaje: '' }
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo enviar el reclamo.' })
  } finally {
    enviandoReclamo.value = false
  }
}

onMounted(cargarLote)
</script>

<style scoped>
.pagina-publica {
  min-height: 100vh;
  background: var(--gradiente-fondo-pagina);
}
.encabezado-publico {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  background: var(--gradiente-oscuro);
}
.logo-publico {
  width: 44px;
  height: 44px;
  object-fit: contain;
}
.texto-suave {
  color: rgba(255, 255, 255, 0.7);
}
.contenido-publico {
  max-width: 640px;
  margin: 0 auto;
  padding: 1.25rem;
}
</style>
