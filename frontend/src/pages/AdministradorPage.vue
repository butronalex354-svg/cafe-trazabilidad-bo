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
          <div class="row no-wrap items-center q-gutter-xs">
            <q-badge :color="colorEstado(u.estado)" :label="etiquetaEstado(u.estado)" />
            <!-- La propia cuenta no tiene menu: no puede desactivarse ni
                 eliminarse a si misma, y su contraseña se cambia desde
                 "Cambiar contraseña" (que pide la actual). -->
            <q-badge v-if="u.id === auth.user?.id" outline color="brown-8" label="Tú" />
            <q-btn
              v-else
              flat
              round
              dense
              color="brown-8"
              icon="more_vert"
              aria-label="Opciones de la cuenta"
            >
              <q-tooltip>Opciones</q-tooltip>
              <q-menu anchor="bottom right" self="top right">
                <q-list dense style="min-width: 200px">
                  <q-item clickable v-close-popup @click="abrirDialogoPassword(u)">
                    <q-item-section avatar><q-icon name="key" color="brown-8" /></q-item-section>
                    <q-item-section>Contraseña nueva</q-item-section>
                  </q-item>
                  <q-item v-if="u.estado === 'aprobado'" clickable v-close-popup @click="desactivar(u)">
                    <q-item-section avatar><q-icon name="block" color="negative" /></q-item-section>
                    <q-item-section class="text-negative">Desactivar</q-item-section>
                  </q-item>
                  <q-item v-if="u.estado === 'desactivado'" clickable v-close-popup @click="reactivar(u)">
                    <q-item-section avatar><q-icon name="check" color="positive" /></q-item-section>
                    <q-item-section class="text-positive">Reactivar</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="eliminar(u)">
                    <q-item-section avatar><q-icon name="delete_forever" color="negative" /></q-item-section>
                    <q-item-section>
                      <q-item-label class="text-negative">Eliminar</q-item-label>
                      <q-item-label caption>Solo si no tiene datos</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
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
          <q-input v-model="nuevoUsuario.email" label="Email" type="email" autocomplete="off" outlined dense bg-color="white" />
          <q-input
            v-model="nuevoUsuario.password"
            label="Contraseña"
            :type="verPasswordNuevo ? 'text' : 'password'"
            autocomplete="new-password"
            outlined
            dense
            bg-color="white"
            :rules="[reglaPassword]"
            lazy-rules
          >
            <template v-slot:append>
              <q-icon
                :name="verPasswordNuevo ? 'visibility' : 'visibility_off'"
                class="cursor-pointer"
                @click="verPasswordNuevo = !verPasswordNuevo"
              />
            </template>
          </q-input>
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

    <!-- Restablecer la contraseña de otra cuenta (cuando alguien se la olvido) -->
    <q-dialog v-model="dialogoPassword">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 420px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Restablecer contraseña</div>
          <div class="text-caption text-grey-7">
            {{ usuarioPassword?.nombre }} · {{ usuarioPassword?.email }}
          </div>
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <div class="text-body2 text-grey-8">
            La contraseña anterior no se puede ver (se guarda cifrada); aquí le pones una nueva.
            Anótala y pásasela a la persona: si tenía la sesión abierta, tendrá que volver a ingresar.
          </div>
          <q-input
            v-model="passwordNueva"
            label="Contraseña nueva"
            :type="verPasswordNueva ? 'text' : 'password'"
            autocomplete="new-password"
            outlined
            dense
            bg-color="white"
            :rules="[reglaPassword]"
            lazy-rules
            @keyup.enter="restablecerPassword"
          >
            <template v-slot:append>
              <q-icon
                :name="verPasswordNueva ? 'visibility' : 'visibility_off'"
                class="cursor-pointer"
                @click="verPasswordNueva = !verPasswordNueva"
              />
            </template>
          </q-input>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated label="Guardar" color="brown-8" no-caps :loading="guardandoPassword" @click="restablecerPassword" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useAdministradorStore } from '@/stores/administrador'
import { useAuthStore } from '@/stores/auth'
import { reglaPassword, passwordValida, MENSAJE_PASSWORD, mensajeDeError } from '@/utils/validacionPassword'

const $q = useQuasar()
const admin = useAdministradorStore()
// Para no mostrar el boton de contraseña en la propia cuenta (esa se
// cambia desde "Cambiar contraseña", que pide la actual).
const auth = useAuthStore()

const dialogoPassword = ref(false)
const usuarioPassword = ref(null)
const passwordNueva = ref('')
const verPasswordNueva = ref(false)
const guardandoPassword = ref(false)

function abrirDialogoPassword (u) {
  usuarioPassword.value = u
  passwordNueva.value = ''
  verPasswordNueva.value = false
  dialogoPassword.value = true
}

async function restablecerPassword () {
  if (!passwordValida(passwordNueva.value)) {
    $q.notify({ type: 'negative', message: MENSAJE_PASSWORD + '.' })
    return
  }
  guardandoPassword.value = true
  try {
    await admin.restablecerPassword(usuarioPassword.value.id, passwordNueva.value)
    $q.notify({ type: 'positive', message: `Listo: ${usuarioPassword.value.nombre} ya puede ingresar con la contraseña nueva.` })
    dialogoPassword.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: mensajeDeError(err, 'No se pudo cambiar la contraseña.') })
  } finally {
    guardandoPassword.value = false
  }
}

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
const verPasswordNuevo = ref(false)

function abrirDialogoCrear () {
  nuevoUsuario.nombre = ''
  nuevoUsuario.email = ''
  nuevoUsuario.password = ''
  nuevoUsuario.rol = 'verificador'
  verPasswordNuevo.value = false
  dialogoCrear.value = true
}

async function crearCuenta () {
  if (!nuevoUsuario.nombre || !nuevoUsuario.email || !nuevoUsuario.password) {
    $q.notify({ type: 'negative', message: 'Completa nombre, email y contraseña.' })
    return
  }
  if (!passwordValida(nuevoUsuario.password)) {
    $q.notify({ type: 'negative', message: MENSAJE_PASSWORD + '.' })
    return
  }
  creando.value = true
  try {
    await admin.crearUsuario({ ...nuevoUsuario })
    $q.notify({ type: 'positive', message: 'Cuenta creada correctamente.' })
    dialogoCrear.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: mensajeDeError(err, 'No se pudo crear la cuenta.') })
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

// Solo borra cuentas vacias: si tiene parcelas, lotes o verificaciones el
// servidor lo rechaza con un mensaje que dice que tiene y que se desactive.
function eliminar (u) {
  $q.dialog({
    title: 'Eliminar cuenta',
    message: `¿Eliminar la cuenta de ${u.nombre} (${u.email})? Esto no se puede deshacer.`,
    class: 'caja-tema',
    cancel: { flat: true, label: 'Cancelar', color: 'grey-8', noCaps: true },
    ok: { unelevated: true, label: 'Eliminar', color: 'negative', noCaps: true }
  }).onOk(async () => {
    try {
      await admin.eliminarUsuario(u.id)
      $q.notify({ type: 'positive', message: `Cuenta de ${u.nombre} eliminada.` })
      admin.cargarReportes()
    } catch (err) {
      $q.notify({ type: 'negative', message: mensajeDeError(err, 'No se pudo eliminar la cuenta.'), multiLine: true, timeout: 9000 })
    }
  })
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
