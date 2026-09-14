<template>
  <q-page class="q-pa-md dashboard-page pagina-tema">
    <template v-if="auth.rol === 'productor'">
      <div class="saludo q-mb-md">
        <div class="text-h5 text-brown-9">Hola, {{ primerNombre }} 👋</div>
        <div class="text-caption text-grey-7">{{ fechaHoy }} · aquí tu resumen de hoy</div>
      </div>

      <q-banner v-if="perfilIncompleto" class="bg-amber-1 text-brown-9 q-mb-md" rounded>
        <template v-slot:avatar><q-icon name="badge" color="amber-8" /></template>
        Te falta completar tus datos personales (CI, teléfono, dirección). La trazabilidad de tus lotes los necesita.
        <template v-slot:action>
          <q-btn unelevated color="orange-9" label="Completar" @click="router.push('/productor/parcelas')" />
        </template>
      </q-banner>

      <div v-if="!cargando && produccion.parcelas.length === 0" class="empty-state q-mb-md">
        <q-icon name="eco" size="40px" color="brown-6" />
        <div class="text-subtitle1 text-brown-8 q-mt-sm">Todavía no tienes parcelas registradas</div>
        <div class="text-caption text-grey-7 q-mb-md">Registra tu primera parcela para empezar a llevar el control de tu producción.</div>
        <q-btn unelevated color="brown-8" icon="add" label="Registrar mi primera parcela" @click="router.push('/productor/parcelas')" />
      </div>

      <template v-else>
        <div class="row q-col-gutter-md q-mb-md items-stretch">
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="map" color="white" size="20px" /></div>
                <div class="stat-numero">{{ produccion.parcelas.length }}</div>
                <div class="stat-label">Parcelas</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="agriculture" color="white" size="20px" /></div>
                <div class="stat-numero">{{ produccion.cosechas.length }}</div>
                <div class="stat-label">Cosechas registradas</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="scale" color="white" size="20px" /></div>
                <div class="stat-numero">{{ totalKg }}</div>
                <div class="stat-label">Kg cosechados</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-sm-3">
            <q-card flat bordered class="stat-card full-height">
              <q-card-section>
                <div class="stat-icono"><q-icon name="event" color="white" size="20px" /></div>
                <div class="stat-numero stat-numero--fecha">{{ ultimaCosechaFecha }}</div>
                <div class="stat-label">Última cosecha</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <div class="row q-col-gutter-sm q-mb-md">
          <div class="col-12 col-sm-6">
            <q-btn unelevated color="brown-8" icon="add" label="Nueva parcela" class="full-width" @click="router.push('/productor/parcelas')" />
          </div>
          <div class="col-12 col-sm-6">
            <q-btn unelevated color="brown-6" icon="add" label="Registrar cosecha" class="full-width" @click="router.push('/productor/produccion')" />
          </div>
        </div>

        <div v-if="parcelasSinCosechas.length" class="text-subtitle2 text-brown-8 q-mb-sm">Qué está pasando</div>
        <q-list v-if="parcelasSinCosechas.length" bordered separator class="rounded-borders lista-avisos">
          <q-item v-for="p in parcelasSinCosechas" :key="p.id" clickable @click="router.push({ path: '/productor/produccion', query: { parcela: p.id } })">
            <q-item-section avatar><q-icon name="info" color="amber-8" /></q-item-section>
            <q-item-section>
              <q-item-label>{{ p.nombre_parcela }} todavía no tiene cosechas registradas</q-item-label>
              <q-item-label caption>Toca para registrar la primera</q-item-label>
            </q-item-section>
            <q-item-section side><q-icon name="chevron_right" /></q-item-section>
          </q-item>
        </q-list>
      </template>
    </template>

    <template v-else>
      <div class="text-h5 q-mb-md">Dashboard del {{ etiquetaRol }}</div>
      <q-banner class="bg-green-1 text-green-10" rounded>
        Bienvenido, {{ auth.user?.nombre }}. Iniciaste sesión correctamente como <b>{{ auth.user?.rol }}</b>.
      </q-banner>
    </template>
  </q-page>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProduccionStore } from '@/stores/produccion'

const auth = useAuthStore()
const produccion = useProduccionStore()
const router = useRouter()
const cargando = ref(true)

const etiquetaRol = computed(() => {
  const nombres = { productor: 'Productor', verificador: 'Verificador', administrador: 'Administrador' }
  return nombres[auth.rol] || ''
})

const primerNombre = computed(() => (auth.user?.nombre || '').split(' ')[0])

const fechaHoy = computed(() => new Date().toLocaleDateString('es-BO', { weekday: 'long', day: 'numeric', month: 'long' }))

const perfilIncompleto = computed(() => {
  const p = produccion.perfil
  return !p || !p.ci || !p.telefono || !p.direccion
})

const totalKg = computed(() => {
  const suma = produccion.cosechas.reduce((acc, c) => acc + Number(c.cantidad || 0), 0)
  return suma % 1 === 0 ? suma : suma.toFixed(2)
})

const ultimaCosechaFecha = computed(() => {
  if (produccion.cosechas.length === 0) return '—'
  const masReciente = [...produccion.cosechas].sort((a, b) => (a.fecha < b.fecha ? 1 : -1))[0]
  return new Date(masReciente.fecha).toLocaleDateString('es-BO')
})

const parcelasSinCosechas = computed(() => produccion.parcelas.filter(p => !p.cosechas_count))

onMounted(async () => {
  if (auth.rol === 'productor') {
    cargando.value = true
    await Promise.all([produccion.cargarPerfil(), produccion.cargarParcelas(), produccion.cargarCosechas()])
    cargando.value = false
  }
})
</script>

<style scoped>
/* El fondo con degradado ahora lo pone la clase compartida "pagina-tema"
   (app.scss), asi se ve igual en Inicio, Parcelas y Producción. */

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
.stat-numero--fecha {
  font-size: 1rem;
}
.stat-label {
  font-size: 0.75rem;
  color: #8a7458;
}

.lista-avisos {
  background: linear-gradient(135deg, #fffaf0 0%, #fbe9cc 100%);
  border-color: rgba(224, 168, 74, 0.4) !important;
}

.empty-state {
  background: linear-gradient(160deg, #fdf1d9 0%, #f6dfab 100%);
  border: 1px dashed rgba(224, 168, 74, 0.4);
  border-radius: 12px;
  padding: 32px 16px;
  text-align: center;
}
</style>
