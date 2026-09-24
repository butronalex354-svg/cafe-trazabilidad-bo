<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Panel del Administrador</div>
    <div class="text-caption text-grey-7 q-mb-md">Supervisión general del sistema: usuarios, cuentas y estado de los lotes</div>

    <!-- Resumen -->
    <div class="row q-col-gutter-md q-mb-md items-stretch">
      <div class="col-6 col-sm-3">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="person_add" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.cuentas_pendientes ?? '—' }}</div>
            <div class="stat-label">Cuentas pendientes</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-sm-3">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="groups" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.productores_aprobados ?? '—' }}</div>
            <div class="stat-label">Productores activos</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-sm-3">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="map" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.total_parcelas ?? '—' }}</div>
            <div class="stat-label">Parcelas registradas</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-6 col-sm-3">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="notifications_active" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.alertas_sin_resolver ?? '—' }}</div>
            <div class="stat-label">Alertas sin resolver</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-md items-stretch">
      <div class="col-4 col-sm-4">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="local_cafe" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.total_lotes ?? '—' }}</div>
            <div class="stat-label">Lotes totales</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-4 col-sm-4">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="task_alt" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.lotes_por_estado?.terminado ?? 0 }}</div>
            <div class="stat-label">Lotes terminados</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-4 col-sm-4">
        <q-card flat bordered class="stat-card full-height">
          <q-card-section>
            <div class="stat-icono"><q-icon name="local_shipping" color="white" size="20px" /></div>
            <div class="stat-numero">{{ admin.reportes?.lotes_por_estado?.exportado ?? 0 }}</div>
            <div class="stat-label">Lotes exportados</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Cuentas pendientes de aprobación -->
    <div class="text-subtitle1 text-brown-9 q-mb-sm">Cuentas pendientes de aprobación</div>
    <div v-if="admin.cuentasPendientes.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders q-mb-lg">
      No hay cuentas esperando aprobación.
    </div>
    <q-list v-else bordered separator class="rounded-borders caja-tema q-mb-lg">
      <q-item v-for="u in admin.cuentasPendientes" :key="u.id">
        <q-item-section avatar>
          <q-icon name="person" color="amber-8" size="28px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ u.nombre }} · {{ etiquetaRol(u.rol) }}</q-item-label>
          <q-item-label caption>
            {{ u.email }}
            <template v-if="u.productor"> · CI {{ u.productor.ci }} · {{ u.productor.telefono }}</template>
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row q-gutter-sm">
            <q-btn dense unelevated color="positive" icon="check" label="Aprobar" no-caps @click="aprobar(u)" />
            <q-btn dense outline color="negative" icon="close" label="Rechazar" no-caps @click="rechazar(u)" />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Todos los usuarios -->
    <div class="row items-center justify-between q-mb-sm q-col-gutter-sm">
      <div class="text-subtitle1 text-brown-9">Todos los usuarios</div>
      <div class="row q-gutter-sm items-center">
        <q-select
          v-model="filtroRol"
          :options="opcionesRol"
          emit-value
          map-options
          dense
          outlined
          bg-color="white"
          style="min-width: 180px"
          @update:model-value="admin.cargarUsuarios({ rol: filtroRol })"
        />
        <q-btn unelevated color="brown-8" icon="person_add" label="Crear cuenta" no-caps @click="abrirDialogoCrear" />
      </div>
    </div>

    <q-inner-loading :showing="admin.cargando" />

    <q-list bordered separator class="rounded-borders caja-tema">
      <q-item v-for="u in admin.usuarios" :key="u.id">
        <q-item-section avatar>
          <q-icon :name="iconoEstado(u.estado)" :color="colorEstado(u.estado)" size="26px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ u.nombre }} · {{ etiquetaRol(u.rol) }}</q-item-label>
          <q-item-label caption>{{ u.email }}</q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row q-gutter-sm items-center">
            <q-badge :color="colorEstado(u.estado)" :label="etiquetaEstado(u.estado)" />
            <q-btn v-if="u.estado === 'aprobado'" dense outline color="negative" icon="block" label="Desactivar" no-caps @click="desactivar(u)" />
            <q-btn v-if="u.estado === 'desactivado'" dense outline color="positive" icon="check" label="Reactivar" no-caps @click="reactivar(u)" />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Crear cuenta de Verificador/Administrador -->
    <q-dialog v-model="dialogoCrear">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 420px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Crear cuenta</div>
          <div class="text-caption text-grey-7">Para Verificador o Administrador (el Productor se registra solo)</div>
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <q-input v-model="nuevoUsuario.nombre" label="Nombre completo" outlined dense bg-color="white" />
          <q-input v-model="nuevoUsuario.email" label="Email" type="email" outlined dense bg-color="white" />
          <q-input v-model="nuevoUsuario.password" label="Contraseña" type="password" outlined dense bg-color="white" />
          <q-select
            v-model="nuevoUsuario.rol"
            :options="[{ label: 'Verificador', value: 'verificador' }, { label: 'Administrador', value: 'administrador' }]"
            emit-value
            map-options
            label="Rol"
            outlined
            dense
            bg-color="white"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated label="Crear" color="brown-8" no-caps :loading="creando" @click="crearCuenta" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useAdministradorStore } from '@/stores/administrador'

const $q = useQuasar()
const admin = useAdministradorStore()

const filtroRol = ref(null)
const opcionesRol = [
  { label: 'Todos los roles', value: null },
  { label: 'Productor', value: 'productor' },
  { label: 'Verificador', value: 'verificador' },
  { label: 'Administrador', value: 'administrador' }
]

const dialogoCrear = ref(false)
const creando = ref(false)
const nuevoUsuario = reactive({ nombre: '', email: '', password: '', rol: 'verificador' })

function abrirDialogoCrear () {
  nuevoUsuario.nombre = ''
  nuevoUsuario.email = ''
  nuevoUsuario.password = ''
  nuevoUsuario.rol = 'verificador'
  dialogoCrear.value = true
}

async function crearCuenta () {
  if (!nuevoUsuario.nombre || !nuevoUsuario.email || !nuevoUsuario.password) {
    $q.notify({ type: 'negative', message: 'Completa nombre, email y contraseña.' })
    return
  }
  creando.value = true
  try {
    await admin.crearUsuario({ ...nuevoUsuario })
    $q.notify({ type: 'positive', message: 'Cuenta creada correctamente.' })
    dialogoCrear.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo crear la cuenta.' })
  } finally {
    creando.value = false
  }
}

async function desactivar (u) {
  try {
    await admin.desactivarUsuario(u.id)
    $q.notify({ type: 'warning', message: `Cuenta de ${u.nombre} desactivada.` })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo desactivar la cuenta.' })
  }
}

async function reactivar (u) {
  try {
    await admin.reactivarUsuario(u.id)
    $q.notify({ type: 'positive', message: `Cuenta de ${u.nombre} reactivada.` })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo reactivar la cuenta.' })
  }
}

function etiquetaRol (rol) {
  return { productor: 'Productor', verificador: 'Verificador', administrador: 'Administrador' }[rol] || rol
}

function etiquetaEstado (estado) {
  return { pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado', desactivado: 'Desactivado' }[estado] || estado
}

function iconoEstado (estado) {
  return { pendiente: 'schedule', aprobado: 'check_circle', rechazado: 'cancel', desactivado: 'block' }[estado] || 'help'
}

function colorEstado (estado) {
  return { pendiente: 'amber-8', aprobado: 'positive', rechazado: 'negative', desactivado: 'grey-7' }[estado] || 'grey'
}

async function aprobar (u) {
  try {
    await admin.aprobarUsuario(u.id)
    $q.notify({ type: 'positive', message: `Cuenta de ${u.nombre} aprobada.` })
    admin.cargarReportes()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo aprobar la cuenta.' })
  }
}

async function rechazar (u) {
  try {
    await admin.rechazarUsuario(u.id)
    $q.notify({ type: 'warning', message: `Cuenta de ${u.nombre} rechazada.` })
    admin.cargarReportes()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo rechazar la cuenta.' })
  }
}

onMounted(async () => {
  await Promise.all([admin.cargarUsuarios(), admin.cargarReportes()])
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
