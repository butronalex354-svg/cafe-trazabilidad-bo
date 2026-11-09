<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-header-cafe">
      <q-toolbar>
        <q-btn flat dense round icon="menu" aria-label="Menú" @click="alternarMenu" />
        <img :src="logoCafeAndino" alt="Café Andino" class="brand-logo-header q-mx-sm" />
        <q-toolbar-title class="titulo-header">
          <span class="gt-xs">CaféTrace Bolivia</span>
          <span class="lt-sm">CaféTrace</span>
        </q-toolbar-title>

        <div v-if="auth.user" class="text-caption q-mr-md gt-xs">{{ auth.user.nombre }} · {{ auth.user.rol }}</div>

        <!-- Notificaciones: por ahora solo muestra la lista, sin marcar leido/etc (eso es funcionalidad futura) -->
        <q-btn flat dense round icon="notifications">
          <q-badge v-if="notificaciones.length" color="red" floating>{{ notificaciones.length }}</q-badge>
          <q-menu anchor="bottom right" self="top right">
            <q-list style="min-width: 260px" class="caja-tema">
              <q-item-label header>Notificaciones</q-item-label>
              <q-item v-if="notificaciones.length === 0">
                <q-item-section class="text-grey-7">No hay notificaciones nuevas.</q-item-section>
              </q-item>
              <q-item v-for="(n, i) in notificaciones" :key="i" clickable v-close-popup>
                <q-item-section avatar><q-icon :name="n.icono" color="brown-7" /></q-item-section>
                <q-item-section>
                  <q-item-label>{{ n.texto }}</q-item-label>
                  <q-item-label caption>{{ n.cuando }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- Ajustes -->
        <q-btn flat dense round icon="settings" class="q-ml-xs">
          <q-menu anchor="bottom right" self="top right" content-class="caja-tema tarjeta-ajustes">
            <div class="row items-center q-pa-md ajustes-perfil">
              <q-avatar size="42px" color="brown-6" text-color="white" class="q-mr-sm">{{ inicialesUsuario }}</q-avatar>
              <div>
                <div class="text-body2 text-weight-medium text-brown-9">{{ auth.user?.nombre }}</div>
                <div class="text-caption text-grey-7">{{ etiquetaRol }}</div>
              </div>
            </div>

            <q-separator />

            <div class="q-pa-md">
              <div class="text-caption text-grey-7 q-mb-sm">Tamaño de letra</div>
              <div class="selector-tamano">
                <div
                  v-for="opcion in opcionesTamano"
                  :key="opcion.valor"
                  class="selector-tamano-opcion"
                  :class="{ 'selector-tamano-opcion--activo': tamanoTexto === opcion.valor }"
                  @click="cambiarTamanoTexto(opcion.valor)"
                >{{ opcion.label }}</div>
              </div>

              <div class="text-caption text-grey-7 q-mb-sm q-mt-md">Apariencia</div>
              <div class="selector-tamano">
                <div
                  v-for="opcion in opcionesTema"
                  :key="opcion.valor"
                  class="selector-tamano-opcion"
                  :class="{ 'selector-tamano-opcion--activo': tema === opcion.valor }"
                  @click="cambiarTema(opcion.valor)"
                ><q-icon :name="opcion.icono" size="16px" class="q-mr-xs" />{{ opcion.label }}</div>
              </div>
            </div>

            <q-separator />

            <q-list class="q-py-xs">
              <q-item clickable v-close-popup class="ajustes-item" @click="abrirMiCuenta">
                <q-item-section avatar><q-icon name="person" color="brown-7" /></q-item-section>
                <q-item-section>Mi cuenta</q-item-section>
              </q-item>
              <q-item v-if="auth.rol === 'productor'" clickable v-close-popup class="ajustes-item" @click="abrirPreferencias">
                <q-item-section avatar><q-icon name="notifications_active" color="brown-7" /></q-item-section>
                <q-item-section>Preferencias de notificación</q-item-section>
              </q-item>
              <q-item v-if="auth.rol === 'administrador'" clickable v-close-popup class="ajustes-item" @click="abrirColorSistema">
                <q-item-section avatar><q-icon name="palette" color="brown-7" /></q-item-section>
                <q-item-section>Color del sistema</q-item-section>
              </q-item>
              <q-item v-if="auth.rol === 'administrador'" clickable v-close-popup class="ajustes-item" @click="router.push('/administrador/catalogos')">
                <q-item-section avatar><q-icon name="menu_book" color="brown-7" /></q-item-section>
                <q-item-section>Catálogos del sistema</q-item-section>
              </q-item>
              <q-item v-if="auth.rol === 'verificador'" clickable v-close-popup class="ajustes-item" @click="router.push('/verificador/historial')">
                <q-item-section avatar><q-icon name="history" color="brown-7" /></q-item-section>
                <q-item-section>Mi historial</q-item-section>
              </q-item>
              <q-item v-if="auth.rol === 'productor'" clickable v-close-popup class="ajustes-item" @click="router.push('/productor/historial')">
                <q-item-section avatar><q-icon name="history" color="brown-7" /></q-item-section>
                <q-item-section>Historial de verificaciones</q-item-section>
              </q-item>
              <q-item clickable v-close-popup class="ajustes-item" @click="dlgAyuda = true">
                <q-item-section avatar><q-icon name="help" color="brown-7" /></q-item-section>
                <q-item-section>Ayuda y soporte</q-item-section>
              </q-item>
            </q-list>

            <q-separator />

            <q-list class="q-py-xs">
              <q-item clickable v-close-popup class="ajustes-item" @click="cerrarSesion">
                <q-item-section avatar><q-icon name="logout" color="negative" /></q-item-section>
                <q-item-section class="text-negative">Cerrar sesión</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :mini="miniState"
      :width="anchoDrawer"
      :mini-width="66"
    >
      <div class="column full-height bg-drawer-cafe">
        <div class="q-pa-md text-center relative-position">
          <div class="logo-plate" :class="{ 'logo-plate--mini': miniState }">
            <img :src="miniState ? logoCafeAndino : logoCompleto" alt="Café Andino" class="brand-logo-drawer" :class="{ 'brand-logo-drawer--mini': miniState }" />
          </div>
        </div>

        <q-separator dark />

        <div class="col nav-wrap q-px-sm q-py-sm">
          <div class="nav-indicador" :class="{ 'nav-indicador--oculto': indiceActivo === -1 }" :style="estiloIndicador"></div>

          <div
            v-for="(item, idx) in itemsMenu"
            :key="item.ruta || item.label"
            class="nav-item"
            :class="{ 'nav-item--activo': idx === indiceActivo, 'nav-item--mini': miniState }"
            @click="irA(item)"
          >
            <q-icon :name="item.icono" size="20px" class="nav-item-icono" />
            <div v-if="!miniState" class="nav-item-textos">
              <div class="nav-item-label">{{ item.label }}</div>
              <div class="nav-item-detalle">{{ item.detalle }}</div>
            </div>
            <q-badge v-if="item.badge" color="red" rounded class="q-ml-xs" :class="{ 'nav-badge--mini': miniState }">{{ item.badge }}</q-badge>

            <!-- En modo colapsado (icono solo) la etiqueta aparece flotando al lado, en vez de agrandar toda la barra. -->
            <q-tooltip v-if="miniState" anchor="center right" self="center left" class="bg-brown-9 text-body2">
              {{ item.label }}
            </q-tooltip>
          </div>
        </div>

        <q-separator dark />

        <div class="q-pa-md row items-center">
          <q-avatar size="36px" color="brown-6" text-color="white" class="q-mr-sm">
            {{ inicialesUsuario }}
          </q-avatar>
          <div v-if="!miniState" class="col">
            <div class="text-body2 text-white">{{ auth.user?.nombre }}</div>
            <div class="text-caption text-grey-6">{{ etiquetaRol }}{{ ubicacionUsuario ? ' · ' + ubicacionUsuario : '' }}</div>
          </div>
        </div>
        <q-btn flat dense no-caps color="negative" icon="logout" :label="miniState ? '' : 'Cerrar sesión'" class="full-width q-mb-sm" @click="cerrarSesion" />
      </div>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- Mi cuenta: datos de la cuenta de acceso (distinto del "registro de
         productor" que se edita en Parcelas) y cambio de contraseña. -->
    <q-dialog v-model="dlgCuenta">
      <q-card class="caja-tema" style="min-width: 320px; max-width: 440px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Mi cuenta</div>
          <div class="text-caption text-grey-7">Datos de tu cuenta de acceso</div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <q-input v-model="formCuenta.nombre" label="Nombre" outlined dense bg-color="white" />
          <q-input v-model="formCuenta.email" label="Email" type="email" outlined dense bg-color="white" />
          <q-input v-model="formCuenta.telefono" label="Teléfono" outlined dense bg-color="white" />
          <q-input :model-value="etiquetaRol" label="Rol" outlined dense readonly bg-color="grey-2" />

          <template v-if="auth.rol === 'verificador'">
            <q-separator />
            <div class="text-subtitle2 text-brown-9">Datos de verificación</div>
            <q-input v-model="formCuenta.entidadVerificadora" label="Entidad / institución" outlined dense bg-color="white" />
            <q-input v-model="formCuenta.acreditacion" label="N° de acreditación" outlined dense bg-color="white" />
          </template>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md">
          <q-btn unelevated color="brown-8" label="Guardar cambios" no-caps :loading="guardandoCuenta" @click="guardarCuenta" />
        </q-card-actions>

        <q-separator class="q-my-sm" />

        <q-card-section>
          <div class="text-subtitle2 text-brown-9 q-mb-sm">Cambiar contraseña</div>
          <div class="q-gutter-md">
            <q-input v-model="formPassword.actual" label="Contraseña actual" type="password" outlined dense bg-color="white" />
            <q-input v-model="formPassword.nueva" label="Contraseña nueva" type="password" outlined dense bg-color="white" :hint="MENSAJE_PASSWORD" :rules="[reglaPassword]" lazy-rules />
            <q-input v-model="formPassword.confirmacion" label="Confirmar contraseña nueva" type="password" outlined dense bg-color="white" />
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated color="brown-8" label="Cambiar contraseña" no-caps :loading="cambiandoPassword" @click="guardarPassword" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Preferencias de notificacion (solo Productor: que tipos de alerta ver) -->
    <q-dialog v-model="dlgPreferencias">
      <q-card class="caja-tema" style="min-width: 300px; max-width: 400px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Preferencias de notificación</div>
          <div class="text-caption text-grey-7">Elegí qué tipos de alerta querés ver en tu bandeja</div>
        </q-card-section>
        <q-card-section class="q-gutter-md">
          <q-toggle v-model="formPreferencias.notificarPlagas" label="Alertas de plagas y enfermedades (IA)" color="brown-8" />
          <q-toggle v-model="formPreferencias.notificarStock" label="Alertas de stock bajo en inventario" color="brown-8" />
        </q-card-section>
        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" no-caps :loading="guardandoPreferencias" @click="guardarPreferencias" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Color del sistema (solo Administrador: afecta a todos los usuarios) -->
    <q-dialog v-model="dlgColorSistema">
      <q-card class="caja-tema" style="min-width: 300px; max-width: 400px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Color del sistema</div>
          <div class="text-caption text-grey-7">Este color se aplica para todos los usuarios del sistema</div>
        </q-card-section>
        <q-card-section class="text-center">
          <input type="color" v-model="colorSistema" class="selector-color" />
          <div class="text-caption text-grey-7 q-mt-sm">{{ colorSistema }}</div>
        </q-card-section>
        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Restablecer" color="grey-8" no-caps @click="restablecerColorSistema" />
          <q-btn unelevated color="brown-8" label="Guardar" no-caps :loading="guardandoColor" @click="guardarColorSistema" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Ayuda y soporte: contenido distinto segun el rol -->
    <q-dialog v-model="dlgAyuda">
      <q-card class="caja-tema" style="min-width: 300px; max-width: 460px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">Ayuda y soporte</div>
          <div class="text-caption text-grey-7">{{ etiquetaRol }}</div>
        </q-card-section>
        <q-card-section>
          <q-list>
            <q-item v-for="(item, idx) in ayudaRol" :key="idx">
              <q-item-section avatar><q-icon :name="item.icono" color="brown-7" /></q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ item.titulo }}</q-item-label>
                <q-item-label caption>{{ item.texto }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cerrar" color="grey-8" no-caps v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import { useCalidadStore } from '@/stores/calidad'
import { useVerificacionStore } from '@/stores/verificacion'
import { useAdministradorStore } from '@/stores/administrador'
import { api } from '@/boot/axios'
import { reglaPassword, passwordValida, MENSAJE_PASSWORD, mensajeDeError } from '@/utils/validacionPassword'
import logoCafeAndino from '@/assets/logo-cafe-andino-badge.png'
import logoCompleto from '@/assets/logo-cafe-andino.png'

const auth = useAuthStore()
const calidad = useCalidadStore()
const verificacion = useVerificacionStore()
const admin = useAdministradorStore()
const route = useRoute()
const router = useRouter()
const $q = useQuasar()

// Tamano de letra (como en el celular). Primero se probo con CSS "zoom",
// pero en el navegador del celular no recalculaba bien el ancho de la
// pantalla (dejaba una franja en blanco al costado). Ahora se cambia el
// tamano base de la letra de verdad (font-size del <html>), que es lo que
// usan los navegadores/celulares para su propio "tamano de texto" - eso si
// se recalcula bien el ancho en cualquier pantalla. El resto de las medidas
// clave (alto de filas del menu, iconos, tarjetas) estan en "rem" en vez de
// "px" para que crezcan junto con el texto y no se corten.
const opcionesTamano = [
  { label: 'Pequeño', valor: 0.9 },
  { label: 'Normal', valor: 1 },
  { label: 'Grande', valor: 1.15 }
]
const tamanoTexto = ref(Number(localStorage.getItem('tamanoTexto')) || 1)

function aplicarTamanoTexto (valor) {
  document.documentElement.style.fontSize = `${16 * valor}px`
}

function cambiarTamanoTexto (valor) {
  tamanoTexto.value = valor
  localStorage.setItem('tamanoTexto', valor)
  aplicarTamanoTexto(valor)
}

aplicarTamanoTexto(tamanoTexto.value)

// Modo claro/oscuro: preferencia personal de este navegador (a diferencia
// del "Color del sistema", que es una config unica que pone el Administrador
// para todos). Se aplica con un atributo en <html> para que el CSS de
// app.scss lo pueda sobreescribir todo junto.
const opcionesTema = [
  { label: 'Claro', valor: 'claro', icono: 'light_mode' },
  { label: 'Oscuro', valor: 'oscuro', icono: 'dark_mode' }
]
const tema = ref(localStorage.getItem('tema') || 'claro')

function aplicarTema (valor) {
  document.documentElement.setAttribute('data-tema', valor)
}

function cambiarTema (valor) {
  tema.value = valor
  localStorage.setItem('tema', valor)
  aplicarTema(valor)
}

aplicarTema(tema.value)

// Arranca en false: en pantalla angosta no se quiere tapar el contenido con
// el overlay del menu apenas se carga la pagina. En pantalla ancha da igual
// (show-if-above lo mantiene siempre visible sin importar este valor).
const leftDrawerOpen = ref(false)
// En pantalla ancha (desktop), el boton hamburguesa colapsa el menu a una
// franja angosta de solo iconos (con el nombre en un tooltip al pasar el
// mouse). En pantalla angosta (celular/ventana chica) el menu ya arranca
// oculto por completo, asi que ahi el boton tiene que mostrarlo/ocultarlo
// entero en vez de "encogerlo" - si no, no hay forma de volver a abrirlo.
const miniState = ref(false)

function alternarMenu () {
  if ($q.screen.lt.md) {
    leftDrawerOpen.value = !leftDrawerOpen.value
  } else {
    miniState.value = !miniState.value
  }
}

// En celular, 230px tapaba casi toda la pantalla (se veia muy invasivo).
// Se deja un poco de fondo visible siempre, sin pasar de 260px en pantallas
// grandes ni angostarse tanto que el texto de los items no entre.
const anchoDrawer = computed(() => Math.min(260, Math.max(220, $q.screen.width * 0.78)))

const etiquetaRol = computed(() => {
  const nombres = { productor: 'Productor', verificador: 'Verificador', administrador: 'Administrador' }
  return nombres[auth.rol] || ''
})

const ubicacionUsuario = computed(() => null) // reservado para cuando el perfil tenga "municipio/región" propio

const inicialesUsuario = computed(() => {
  const nombre = auth.user?.nombre || ''
  return nombre.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
})

// El menu lateral, como en el diseño de Figma del usuario: los modulos que
// todavia no estan construidos quedan visibles (para que se vea el mapa
// completo del sistema) pero avisan "proximamente" en vez de dar un 404.
const itemsMenu = computed(() => {
  if (auth.rol === 'administrador') {
    return [
      { label: 'Panel', detalle: 'Usuarios y reportes', icono: 'dashboard', ruta: '/administrador' },
      { label: 'Buscar lotes', detalle: 'Búsqueda general', icono: 'travel_explore', ruta: '/administrador/buscar' },
      { label: 'Reclamos', detalle: 'Reclamos de compradores', icono: 'support_agent', ruta: '/administrador/reclamos', badge: admin.reclamosPendientes || null },
      { label: 'Exportación', detalle: 'Autorizar lotes verificados', icono: 'local_shipping', ruta: '/administrador/exportacion', badge: admin.lotesParaExportar.length || null },
      { label: 'Auditoría', detalle: 'Historial de cambios', icono: 'history', ruta: '/administrador/auditoria' }
    ]
  }
  if (auth.rol === 'verificador') {
    return [
      { label: 'Todas', detalle: 'Todas las solicitudes', icono: 'list_alt', ruta: '/verificador' },
      { label: 'Pendientes', detalle: 'Por revisar', icono: 'schedule', ruta: '/verificador/pendientes', badge: verificacion.pendientes || null },
      { label: 'Observados', detalle: 'Con correcciones pedidas', icono: 'visibility', ruta: '/verificador/observados' },
      { label: 'Verificados', detalle: 'Ya aprobados', icono: 'check_circle', ruta: '/verificador/verificados' },
      { label: 'Rechazados', detalle: 'No aprobados', icono: 'cancel', ruta: '/verificador/rechazados' }
    ]
  }
  if (auth.rol !== 'productor') {
    return [{ label: 'Dashboard', detalle: '', icono: 'dashboard', ruta: `/${auth.rol}` }]
  }
  return [
    { label: 'Inicio', detalle: 'Resumen general', icono: 'home', ruta: '/productor' },
    { label: 'Parcelas', detalle: 'Gestión de parcelas', icono: 'map', ruta: '/productor/parcelas' },
    { label: 'Producción', detalle: 'Cosechas y lotes', icono: 'agriculture', ruta: '/productor/produccion' },
    { label: 'Monitoreo', detalle: 'Clima y observaciones', icono: 'wb_cloudy', ruta: '/productor/monitoreo' },
    { label: 'Procesamiento', detalle: 'Etapas post-cosecha', icono: 'settings_suggest', ruta: '/productor/procesamiento' },
    { label: 'Calidad', detalle: 'Diagnóstico IA', icono: 'eco', ruta: '/productor/calidad' },
    { label: 'Inventario', detalle: 'Stock por lote', icono: 'inventory_2', ruta: '/productor/inventario' },
    { label: 'Alertas', detalle: 'Plagas y avisos', icono: 'notifications_active', ruta: '/productor/calidad?seccion=alertas', badge: calidad.alertasNoLeidas }
  ]
})

const notificaciones = ref([
  // Placeholder de ejemplo: cuando existan modulos reales de Calidad/Alertas,
  // esto se llenara desde el backend en vez de estar escrito a mano aqui.
])

// Carga el conteo real de alertas no leidas para el globito del menu
// (Modulo 4 - Calidad, RF09), apenas entra el productor al sistema.
onMounted(() => {
  if (auth.rol === 'productor') {
    calidad.cargarAlertas()
  }
  if (auth.rol === 'verificador') {
    verificacion.cargarContadorPendientes()
  }
  if (auth.rol === 'administrador') {
    admin.cargarReclamos('pendiente')
    admin.cargarLotesParaExportar()
  }
})

function esRutaActual (ruta) {
  // "ruta" puede traer un query string pegado (ej. "/productor/calidad?seccion=alertas"),
  // asi que se compara solo la parte del path, no la URL completa.
  return !!ruta && route.path === ruta.split('?')[0]
}

// "Burbuja" flotante que marca SOLO la seccion activa (no sigue al mouse en
// hover, el usuario pidio que el efecto pase nomas al hacer clic/navegar).
// Como todos los items miden lo mismo (3.5rem en el CSS), la posicion se
// calcula solo con el indice. ROW_HEIGHT esta en px reales, asi que tiene
// que escalar junto con el tamano de letra (3.5rem = 56px a 16px base).
const ROW_HEIGHT = computed(() => 56 * tamanoTexto.value)

const indiceActivo = computed(() => itemsMenu.value.findIndex(i => esRutaActual(i.ruta)))

// Efecto "gota liquida" (video de referencia: se aplasta/estira mientras se
// desliza y recien al llegar recupera su forma normal) en vez de moverse
// como un bloque rigido. Se logra solo con CSS (transform + transition), sin
// necesitar los filtros SVG "goo" del ejemplo.
const apachurrado = ref(false)
let temporizadorForma = null

watch(indiceActivo, (nuevo, anterior) => {
  if (nuevo === anterior || nuevo === -1) return
  apachurrado.value = true
  clearTimeout(temporizadorForma)
  temporizadorForma = setTimeout(() => { apachurrado.value = false }, 260)
})

const estiloIndicador = computed(() => {
  const y = Math.max(indiceActivo.value, 0) * ROW_HEIGHT.value
  const escalaX = apachurrado.value ? 1.12 : 1
  const escalaY = apachurrado.value ? 0.72 : 1
  return {
    transform: `translateY(${y}px) scale(${escalaX}, ${escalaY})`
  }
})

function irA (item) {
  if (!item.ruta) {
    proximamente()
    return
  }
  router.push(item.ruta)
}

function proximamente () {
  $q.notify({ message: 'Todavía no está construido — próximamente.', color: 'grey-8', icon: 'schedule' })
}

// "Mi cuenta": nombre/email de la cuenta de acceso (auth.user), y cambio de
// contraseña. Es un dato distinto del "registro de productor" (Parcelas).
const dlgCuenta = ref(false)
const formCuenta = ref({ nombre: '', email: '', telefono: '', entidadVerificadora: '', acreditacion: '' })
const formPassword = ref({ actual: '', nueva: '', confirmacion: '' })
const guardandoCuenta = ref(false)
const cambiandoPassword = ref(false)

function abrirMiCuenta () {
  formCuenta.value = {
    nombre: auth.user?.nombre || '',
    email: auth.user?.email || '',
    telefono: auth.user?.telefono || '',
    entidadVerificadora: auth.user?.entidad_verificadora || '',
    acreditacion: auth.user?.acreditacion || ''
  }
  formPassword.value = { actual: '', nueva: '', confirmacion: '' }
  dlgCuenta.value = true
}

async function guardarCuenta () {
  if (!formCuenta.value.nombre || !formCuenta.value.email) {
    $q.notify({ type: 'negative', message: 'Completa nombre y email.' })
    return
  }
  guardandoCuenta.value = true
  try {
    await auth.actualizarCuenta(formCuenta.value.nombre, formCuenta.value.email, {
      telefono: formCuenta.value.telefono,
      entidad_verificadora: formCuenta.value.entidadVerificadora,
      acreditacion: formCuenta.value.acreditacion
    })
    $q.notify({ type: 'positive', message: 'Cuenta actualizada.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo actualizar la cuenta.' })
  } finally {
    guardandoCuenta.value = false
  }
}

async function guardarPassword () {
  const { actual, nueva, confirmacion } = formPassword.value
  if (!actual || !nueva || !confirmacion) {
    $q.notify({ type: 'negative', message: 'Completa los 3 campos de contraseña.' })
    return
  }
  if (!passwordValida(nueva)) {
    $q.notify({ type: 'negative', message: MENSAJE_PASSWORD + '.' })
    return
  }
  if (nueva !== confirmacion) {
    $q.notify({ type: 'negative', message: 'La confirmación no coincide con la contraseña nueva.' })
    return
  }
  cambiandoPassword.value = true
  try {
    const mensaje = await auth.cambiarPassword(actual, nueva, confirmacion)
    $q.notify({ type: 'positive', message: mensaje || 'Contraseña actualizada.' })
    formPassword.value = { actual: '', nueva: '', confirmacion: '' }
  } catch (err) {
    $q.notify({ type: 'negative', message: mensajeDeError(err, 'No se pudo cambiar la contraseña.') })
  } finally {
    cambiandoPassword.value = false
  }
}

async function cerrarSesion () {
  await auth.logout()
  router.push('/login')
}

// --- Preferencias de notificacion (solo Productor) ---
const dlgPreferencias = ref(false)
const guardandoPreferencias = ref(false)
const formPreferencias = ref({ notificarPlagas: true, notificarStock: true })

function abrirPreferencias () {
  formPreferencias.value = {
    notificarPlagas: auth.user?.notificar_plagas ?? true,
    notificarStock: auth.user?.notificar_stock ?? true
  }
  dlgPreferencias.value = true
}

async function guardarPreferencias () {
  guardandoPreferencias.value = true
  try {
    await auth.actualizarPreferencias(formPreferencias.value.notificarPlagas, formPreferencias.value.notificarStock)
    $q.notify({ type: 'positive', message: 'Preferencias actualizadas.' })
    dlgPreferencias.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudieron guardar las preferencias.' })
  } finally {
    guardandoPreferencias.value = false
  }
}

// --- Color del sistema (solo Administrador, afecta a todos) ---
const dlgColorSistema = ref(false)
const guardandoColor = ref(false)
const colorSistema = ref('#d4a62f')
const COLOR_POR_DEFECTO = '#d4a62f'

function aplicarColorSistema (hex) {
  const r = parseInt(hex.slice(1, 3), 16)
  const g = parseInt(hex.slice(3, 5), 16)
  const b = parseInt(hex.slice(5, 7), 16)
  const oscurecer = (factor) => {
    const rO = Math.round(r * factor)
    const gO = Math.round(g * factor)
    const bO = Math.round(b * factor)
    return `#${rO.toString(16).padStart(2, '0')}${gO.toString(16).padStart(2, '0')}${bO.toString(16).padStart(2, '0')}`
  }
  const raiz = document.documentElement.style
  raiz.setProperty('--color-primario', hex)
  raiz.setProperty('--color-secundario', oscurecer(0.78))
  raiz.setProperty('--color-primario-oscuro', oscurecer(0.5))
  raiz.setProperty('--color-acento', hex)
}

async function cargarColorSistema () {
  try {
    const { data } = await api.get('/configuracion')
    colorSistema.value = data.color_primario
    aplicarColorSistema(data.color_primario)
  } catch {
    // si falla, se queda con el color por defecto del CSS
  }
}

function abrirColorSistema () {
  dlgColorSistema.value = true
}

async function guardarColorSistema () {
  guardandoColor.value = true
  try {
    await api.put('/configuracion', { color_primario: colorSistema.value })
    aplicarColorSistema(colorSistema.value)
    $q.notify({ type: 'positive', message: 'Color del sistema actualizado para todos los usuarios.' })
    dlgColorSistema.value = false
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar el color.' })
  } finally {
    guardandoColor.value = false
  }
}

async function restablecerColorSistema () {
  colorSistema.value = COLOR_POR_DEFECTO
  await guardarColorSistema()
}

cargarColorSistema()

// --- Ayuda y soporte (contenido distinto por rol) ---
const dlgAyuda = ref(false)
const ayudaPorRol = {
  productor: [
    { icono: 'map', titulo: 'Registrar una parcela', texto: 'Andá a "Parcelas" → "Nueva parcela", marcá la ubicación en el mapa y completá el tamaño y tipo de suelo.' },
    { icono: 'agriculture', titulo: 'Registrar una cosecha', texto: 'En "Producción" → "Registrar cosecha". Se genera un código de lote único automáticamente.' },
    { icono: 'eco', titulo: 'Diagnóstico de plagas', texto: 'En "Calidad" subí una foto de la hoja o el fruto, la IA te dice si hay signos de enfermedad.' },
    { icono: 'verified', titulo: 'Solicitar verificación', texto: 'En "Producción", cada lote tiene un botón "Solicitar verificación" para que el Verificador lo revise.' },
    { icono: 'support_agent', titulo: '¿Problemas?', texto: 'Contactá al administrador del sistema desde tu institución o asociación cafetalera.' }
  ],
  verificador: [
    { icono: 'inbox', titulo: 'Bandeja de solicitudes', texto: 'En el menú lateral, "Pendientes" muestra los lotes que los productores enviaron a revisión.' },
    { icono: 'visibility', titulo: 'Revisar un lote', texto: 'Hacé clic en "Revisar" para ver el mapa de la parcela, el procesamiento y los controles de calidad antes de decidir.' },
    { icono: 'rule', titulo: 'Resolver una solicitud', texto: 'Elegí "Verificado", "Observado" (si falta corregir algo) o "Rechazado", y dejá una observación si corresponde.' },
    { icono: 'history', titulo: 'Mi historial', texto: 'En este menú de ajustes podés ver todas las verificaciones que vos mismo resolviste.' }
  ],
  administrador: [
    { icono: 'person_add', titulo: 'Aprobar cuentas nuevas', texto: 'En el Panel, las cuentas de productores recién registrados aparecen como "Pendientes" hasta que las apruebes.' },
    { icono: 'group_add', titulo: 'Crear Verificador/Administrador', texto: 'Esos 2 roles no se auto-registran: los creás vos mismo desde el Panel, botón "Crear cuenta".' },
    { icono: 'local_shipping', titulo: 'Autorizar exportación', texto: 'Un lote aparece en "Exportación" recién cuando el Verificador ya lo marcó como "Verificado".' },
    { icono: 'history', titulo: 'Auditoría', texto: 'Revisá el historial completo de cambios de cuentas y acciones del sistema en "Auditoría".' }
  ]
}
const ayudaRol = computed(() => ayudaPorRol[auth.rol] || [])
</script>

<style scoped>
/* El header y el drawer deben verse como una sola superficie continua en la
   esquina donde se tocan (antes el header era bg-brown-8 y el drawer un
   degradado mas oscuro con borde propio - se notaba la union, "separado"). */
.bg-header-cafe {
  /* Mismo degradado exacto que el drawer (antes eran 2 degradados distintos
     -uno horizontal, otro diagonal- y en la esquina donde se tocan se notaba
     el cambio de color como si fuera un espacio/costura). */
  background: var(--gradiente-oscuro);
}

/* Fondo del drawer: degradado sutil café oscuro (no un blanco plano, no un
   color plano tampoco), "suavecito" pero con combinacion como se pidio. */
.bg-drawer-cafe {
  background: var(--gradiente-oscuro);
}

.brand-logo-header {
  height: 42px;
  width: auto;
  flex-shrink: 0;
}
.titulo-header {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Placa suave detras del logo: el texto "CAFE ANDINO" del logo es oscuro,
   y sobre un fondo tan oscuro como el del drawer se perderia. Este resplandor
   claro de fondo le da suficiente contraste sin tapar el logo con una caja. */
.logo-plate {
  display: inline-block;
  padding: 14px;
  border-radius: 20px;
  background: radial-gradient(circle, rgba(255, 226, 178, 0.95) 0%, rgba(224, 168, 74, 0.55) 45%, rgba(138, 90, 30, 0.15) 70%, transparent 85%);
}
.brand-logo-drawer {
  width: 150px;
  height: auto;
  object-fit: contain;
  display: block;
}

.nav-wrap {
  position: relative;
  /* Cuando la ventana es baja, los 7 items del menu no entraban completos y
     se encimaban con el separador de abajo (usuario/cerrar sesion). Ahora
     el menu hace su propio scroll interno en vez de desbordarse. */
  overflow-y: auto;
  /* Barra de scroll con color del tema en vez del gris/blanco por defecto. */
  scrollbar-width: thin;
  scrollbar-color: #d6a84a #2a1f18;
}
.nav-wrap::-webkit-scrollbar {
  width: 8px;
}
.nav-wrap::-webkit-scrollbar-track {
  background: #2a1f18;
}
.nav-wrap::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #e0a84a, #8a5a1e);
  border-radius: 8px;
}
.nav-wrap::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #f0c878, #a86a28);
}

/* La "burbuja"/gota que se desliza entre los items (imagen de referencia:
   Elastic Navigation) con la misma curva elastica que el usuario mando. */
.nav-indicador {
  position: absolute;
  left: 0.5rem;
  right: 0.5rem;
  top: 0.5rem;
  height: 3rem;
  border-radius: 0.875rem;
  background: var(--gradiente-principal);
  box-shadow: 0 3px 12px rgba(224, 168, 74, 0.35);
  /* La curva elastica original (cubic-bezier(.68,-.55,.265,1.55)) tiene
     "anticipacion": antes de moverse hacia el destino, retrocede un toque
     de mas - eso se veia como un salto raro antes de llegar al item de
     verdad. Con una curva sin ese retroceso, se desliza directo al lugar. */
  transition: transform .3s cubic-bezier(.4, 0, .2, 1), opacity .2s ease;
  transform-origin: center;
  z-index: 0;
  pointer-events: none;
}
.nav-indicador--oculto {
  opacity: 0;
}

.nav-item {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  height: 3.5rem;
  padding: 0 0.875rem;
  gap: 0.75rem;
  cursor: pointer;
  color: rgba(255, 255, 255, 0.65);
  transition: color .25s ease;
}
.nav-item:hover {
  color: rgba(255, 255, 255, 0.9);
}
.nav-item-icono {
  color: inherit;
  flex-shrink: 0;
}
.nav-item-textos {
  min-width: 0;
  overflow: hidden;
}
.nav-item-label {
  font-size: 0.875rem;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nav-item-detalle {
  font-size: 0.6875rem;
  line-height: 1.3;
  opacity: .8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nav-item--activo {
  color: #fff6e8;
}
.nav-item--activo .nav-item-detalle {
  color: #fbe6c4;
}

/* Modo colapsado: icono centrado, sin texto (el nombre sale en el tooltip). */
.nav-item--mini {
  padding: 0;
  justify-content: center;
}
.nav-badge--mini {
  position: absolute;
  top: 6px;
  right: 10px;
}

.logo-plate--mini {
  padding: 8px;
  border-radius: 14px;
}
.brand-logo-drawer--mini {
  width: 38px;
}
</style>
