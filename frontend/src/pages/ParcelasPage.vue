<template>
  <q-page class="q-pa-md produccion-page pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-md">Parcelas</div>

    <q-banner v-if="perfilIncompleto" class="bg-amber-1 text-brown-9 q-mb-md" rounded>
      <template v-slot:avatar><q-icon name="info" color="amber-8" /></template>
      Completa tu CI, teléfono y dirección en tu registro de productor antes de registrar parcelas.
    </q-banner>

    <div class="text-h6 text-brown-8 q-mb-sm">Mi registro de productor</div>
    <q-table
      flat
      :bordered="!$q.screen.lt.sm"
      class="q-mb-lg"
      :class="{ 'tarjeta-perfil': !$q.screen.lt.sm }"
      :rows="filasProductor"
      :columns="columnasProductor"
      row-key="id"
      hide-bottom
      :pagination="{ rowsPerPage: 0 }"
      :grid="$q.screen.lt.sm"
    >
      <!-- En celular (grid) cada dato se ve apilado en una tarjeta, sin scroll horizontal -->
      <template v-slot:item="props">
        <q-card flat bordered class="full-width q-mb-sm tarjeta-perfil">
          <q-card-section>
            <div v-for="col in props.cols.filter(c => c.name !== 'acciones')" :key="col.name" class="row justify-between q-py-xs">
              <span class="text-caption text-grey-7">{{ col.label }}</span>
              <span class="text-weight-medium">{{ col.value }}</span>
            </div>
            <q-btn flat dense color="brown-7" icon="edit" label="Editar" class="full-width q-mt-sm" @click="abrirPerfil" />
          </q-card-section>
        </q-card>
      </template>

      <template v-slot:body-cell-acciones="props">
        <q-td :props="props">
          <q-btn flat dense round icon="edit" color="brown-7" @click="abrirPerfil" />
        </q-td>
      </template>
    </q-table>

    <div class="row items-center justify-between q-mb-sm">
      <div class="text-h6 text-brown-8">Mis parcelas</div>
      <q-btn unelevated color="brown-8" icon="add" label="Nueva parcela" @click="abrirNuevaParcela" />
    </div>

    <q-input
      v-if="produccion.parcelas.length > 0"
      v-model="busqueda"
      dense
      outlined
      clearable
      bg-color="white"
      :placeholder="$q.screen.lt.sm ? 'Buscar parcela...' : 'Buscar por nombre, ubicación o tipo de suelo...'"
      class="q-mb-md buscador-parcelas"
    >
      <template v-slot:prepend><q-icon name="search" /></template>
    </q-input>

    <q-inner-loading :showing="produccion.cargando" />

    <div v-if="!produccion.cargando && produccion.parcelas.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no registraste ninguna parcela. Usa "Nueva parcela" para empezar.
    </div>

    <div v-if="produccion.parcelas.length > 0 && parcelasFiltradas.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Ninguna parcela coincide con "{{ busqueda }}".
    </div>

    <q-list v-if="parcelasFiltradas.length > 0" bordered separator class="rounded-borders caja-tema">
      <q-expansion-item
        v-for="parcela in parcelasFiltradas"
        :key="parcela.id"
        group="parcelas"
        class="parcela-expansion"
        :ripple="false"
      >
        <template v-slot:header>
          <q-item-section avatar>
            <q-icon name="eco" color="brown-7" />
          </q-item-section>
          <q-item-section>
            <div class="parcela-titulo">{{ parcela.nombre_parcela }}</div>
            <div class="parcela-subtitulo">{{ cabeceraParcela(parcela) }}</div>
          </q-item-section>
        </template>

        <q-card class="parcela-detalle caja-tema">
          <q-card-section class="row q-col-gutter-md items-stretch">
            <div class="col-12 col-sm-6">
              <div class="datos-parcela">
                <div v-if="parcela.ubicacion" class="dato-fila">
                  <q-icon name="place" size="18px" color="brown-6" />
                  <span>{{ parcela.ubicacion }}</span>
                </div>
                <div v-if="parcela.tipo_suelo" class="dato-fila">
                  <q-icon name="grass" size="18px" color="brown-6" />
                  <span>Suelo {{ parcela.tipo_suelo }}</span>
                </div>
                <div v-if="parcela.tamano_terreno" class="dato-fila">
                  <q-icon name="straighten" size="18px" color="brown-6" />
                  <span>{{ parcela.tamano_terreno }} ha</span>
                </div>
              </div>

              <div class="q-mt-md row q-gutter-sm">
                <q-btn outline dense color="brown-7" icon="edit" label="Editar" @click="abrirEditarParcela(parcela)" />
                <q-btn outline dense color="negative" icon="delete" label="Eliminar" @click="confirmarEliminarParcela(parcela)" />
                <q-btn dense unelevated color="brown-6" icon="agriculture" label="Ver lotes" @click="verLotes(parcela)" />
              </div>
            </div>

            <div class="col-12 col-sm-6">
              <ParcelaMapView :ubicacion="parcela.ubicacion" :etiqueta="parcela.nombre_parcela" :height="170" fill />
            </div>
          </q-card-section>
        </q-card>
      </q-expansion-item>
    </q-list>

    <!-- Dialogo: perfil del productor -->
    <q-dialog v-model="dlgPerfil">
      <q-card style="min-width: 320px; max-width: 420px" class="caja-tema">
        <q-card-section class="text-h6">Mi perfil de productor</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="formPerfil.nombre_completo" label="Nombre completo" dense outlined />
          <q-input v-model="formPerfil.ci" label="CI" dense outlined />
          <q-input v-model="formPerfil.telefono" label="Teléfono" dense outlined />
          <q-input v-model="formPerfil.whatsapp" label="WhatsApp" dense outlined />
          <q-input v-model="formPerfil.direccion" label="Dirección" dense outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardando" @click="guardarPerfil" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dialogo: parcela (nueva / editar) -->
    <q-dialog v-model="dlgParcela">
      <q-card style="min-width: 320px; max-width: 460px" class="caja-tema">
        <q-card-section class="text-h6">{{ parcelaEditando ? 'Editar parcela' : 'Nueva parcela' }}</q-card-section>
        <q-card-section class="q-gutter-sm">
          <q-input v-model="formParcela.nombre_parcela" label="Nombre de la parcela" dense outlined />

          <ParcelaMapPicker v-model="formParcela.ubicacion" />
          <q-input v-model="formParcela.ubicacion" label="Ubicación (lat, lng)" dense outlined hint="Se llena solo al marcar en el mapa, o edítala a mano">
            <template v-slot:append>
              <q-btn flat dense round icon="my_location" color="brown-7" @click="usarUbicacionActual" />
            </template>
          </q-input>

          <q-select
            v-model="formParcela.tipo_suelo"
            label="Tipo de suelo"
            dense
            outlined
            use-input
            new-value-mode="add-unique"
            :options="opcionesTipoSuelo"
          />

          <q-input v-model.number="formParcela.tamano_terreno" type="number" step="0.01" label="Tamaño de terreno (ha)" dense outlined />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" :loading="guardando" @click="guardarParcela" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useProduccionStore } from '@/stores/produccion'
import ParcelaMapPicker from '@/components/ParcelaMapPicker.vue'
import ParcelaMapView from '@/components/ParcelaMapView.vue'

const $q = useQuasar()
const router = useRouter()
const produccion = useProduccionStore()

const guardando = ref(false)

// Buscador: util cuando ya hay muchas parcelas registradas (RF02).
const busqueda = ref('')
const parcelasFiltradas = computed(() => {
  const texto = busqueda.value?.trim().toLowerCase()
  if (!texto) return produccion.parcelas
  return produccion.parcelas.filter(p =>
    [p.nombre_parcela, p.ubicacion, p.tipo_suelo].some(campo => campo?.toLowerCase().includes(texto))
  )
})

// --- Perfil (RF01: vista de listado y edición de Productores) ---
const dlgPerfil = ref(false)
const formPerfil = ref({ nombre_completo: '', ci: '', telefono: '', whatsapp: '', direccion: '' })

const perfilIncompleto = computed(() => {
  const p = produccion.perfil
  return !p || !p.ci || !p.telefono || !p.direccion
})

const columnasProductor = [
  { name: 'nombre_completo', label: 'Nombre completo', field: 'nombre_completo', align: 'left' },
  { name: 'ci', label: 'CI', field: 'ci', align: 'left' },
  { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
  { name: 'whatsapp', label: 'WhatsApp', field: 'whatsapp', align: 'left' },
  { name: 'direccion', label: 'Dirección', field: 'direccion', align: 'left' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' }
]

// Cada productor solo administra su propio registro (no hay listado de
// otros productores, eso sería una vista aparte del rol Administrador),
// asi que la tabla siempre muestra una sola fila: la del usuario autenticado.
const filasProductor = computed(() => produccion.perfil ? [produccion.perfil] : [])

const opcionesTipoSuelo = ['Franco', 'Franco-arcilloso', 'Franco-arenoso', 'Arcilloso', 'Arenoso', 'Volcánico']

async function abrirPerfil () {
  if (!produccion.perfil) await produccion.cargarPerfil()
  formPerfil.value = { ...produccion.perfil }
  dlgPerfil.value = true
}

async function guardarPerfil () {
  guardando.value = true
  try {
    await produccion.actualizarPerfil(formPerfil.value)
    $q.notify({ type: 'positive', message: 'Perfil actualizado.' })
    dlgPerfil.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar el perfil.' })
  } finally {
    guardando.value = false
  }
}

// --- Parcelas ---
const dlgParcela = ref(false)
const parcelaEditando = ref(null)
const formParcela = ref({ nombre_parcela: '', ubicacion: '', tipo_suelo: null, tamano_terreno: null })

function abrirNuevaParcela () {
  parcelaEditando.value = null
  formParcela.value = { nombre_parcela: '', ubicacion: '', tipo_suelo: null, tamano_terreno: null }
  dlgParcela.value = true
}

function abrirEditarParcela (parcela) {
  parcelaEditando.value = parcela
  formParcela.value = {
    nombre_parcela: parcela.nombre_parcela,
    ubicacion: parcela.ubicacion,
    tipo_suelo: parcela.tipo_suelo,
    tamano_terreno: parcela.tamano_terreno
  }
  dlgParcela.value = true
}

function usarUbicacionActual () {
  if (!navigator.geolocation) {
    $q.notify({ type: 'warning', message: 'Tu navegador no soporta geolocalización.' })
    return
  }
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      formParcela.value.ubicacion = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`
    },
    () => $q.notify({ type: 'negative', message: 'No se pudo obtener tu ubicación.' })
  )
}

async function guardarParcela () {
  guardando.value = true
  try {
    if (parcelaEditando.value) {
      await produccion.editarParcela(parcelaEditando.value.id, formParcela.value)
    } else {
      await produccion.crearParcela(formParcela.value)
    }
    $q.notify({ type: 'positive', message: 'Parcela guardada.' })
    dlgParcela.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar la parcela.' })
  } finally {
    guardando.value = false
  }
}

function confirmarEliminarParcela (parcela) {
  $q.dialog({
    title: 'Eliminar parcela',
    message: `¿Seguro que quieres eliminar "${parcela.nombre_parcela}"?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    try {
      await produccion.eliminarParcela(parcela.id)
      $q.notify({ type: 'positive', message: 'Parcela eliminada.' })
    } catch (err) {
      $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo eliminar.' })
    }
  })
}

function cabeceraParcela (parcela) {
  const partes = []
  if (parcela.tamano_terreno) partes.push(`${parcela.tamano_terreno} ha`)
  partes.push(`${parcela.cosechas_count ?? 0} cosecha(s)`)
  return partes.join(' · ')
}

function verLotes (parcela) {
  router.push({ path: '/productor/produccion', query: { parcela: parcela.id } })
}

onMounted(async () => {
  await Promise.all([produccion.cargarPerfil(), produccion.cargarParcelas()])
})
</script>

<style scoped>
/* El fondo con degradado ahora lo pone la clase compartida "pagina-tema"
   (app.scss), asi se ve igual en Inicio, Parcelas y Producción. */

.buscador-parcelas {
  max-width: 420px;
}

/* Tarjeta de "Mi registro de productor": color mas vivo, no blanco/suavecito. */
.tarjeta-perfil {
  background: linear-gradient(160deg, #fbe6bb 0%, #f3cd83 100%) !important;
  border: 1px solid rgba(184, 132, 42, 0.5) !important;
}
/* En celular (modo grid) el contenedor de la tabla no debe tener su propio
   fondo/caja - solo la tarjeta de adentro, para no verse como caja doble. */
:deep(.q-table__grid-content) {
  background: transparent;
  box-shadow: none;
}
:deep(.tarjeta-perfil thead th),
:deep(.tarjeta-perfil tbody td) {
  background: transparent !important;
}

.datos-parcela {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.dato-fila {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #4a3a2a;
}

.parcela-titulo {
  font-weight: 600;
  font-size: 15px;
  line-height: 1.5;
  color: #4a3a2a;
}
.parcela-subtitulo {
  font-size: 13px;
  line-height: 1.5;
  color: #6b5641;
}

/* La fila del encabezado (icono + titulo + subtitulo) traia una altura fija
   pensada para una sola linea de texto; con titulo+subtitulo (2 lineas) el
   contenido no entraba y la segunda linea se cortaba. Se le da altura libre. */
:deep(.parcela-expansion > .q-expansion-item__container > .q-item) {
  min-height: 64px;
  height: auto;
  overflow: visible;
  padding-top: 10px;
  padding-bottom: 10px;
}
</style>
