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
          <q-menu anchor="bottom right" self="top right">
            <q-list style="min-width: 240px" class="caja-tema">
              <q-item-label header>Ajustes</q-item-label>

              <q-item-label header class="text-caption q-pb-none">Tamaño de letra</q-item-label>
              <q-item class="q-pt-none">
                <q-item-section>
                  <div class="row q-gutter-xs">
                    <q-btn
                      v-for="opcion in opcionesTamano"
                      :key="opcion.valor"
                      dense
                      no-caps
                      :outline="tamanoTexto !== opcion.valor"
                      :unelevated="tamanoTexto === opcion.valor"
                      :color="tamanoTexto === opcion.valor ? 'brown-8' : 'brown-6'"
                      :label="opcion.label"
                      class="col"
                      @click="cambiarTamanoTexto(opcion.valor)"
                    />
                  </div>
                </q-item-section>
              </q-item>

              <q-separator />

              <q-item clickable v-close-popup @click="proximamente">
                <q-item-section avatar><q-icon name="person" /></q-item-section>
                <q-item-section>Mi cuenta</q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="proximamente">
                <q-item-section avatar><q-icon name="notifications_active" /></q-item-section>
                <q-item-section>Preferencias de notificación</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="cerrarSesion">
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
  </q-layout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import logoCafeAndino from '@/assets/logo-cafe-andino-badge.png'
import logoCompleto from '@/assets/logo-cafe-andino.png'

const auth = useAuthStore()
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
  if (auth.rol !== 'productor') {
    return [{ label: 'Dashboard', detalle: '', icono: 'dashboard', ruta: `/${auth.rol}` }]
  }
  return [
    { label: 'Inicio', detalle: 'Resumen general', icono: 'home', ruta: '/productor' },
    { label: 'Parcelas', detalle: 'Gestión de parcelas', icono: 'map', ruta: '/productor/parcelas' },
    { label: 'Producción', detalle: 'Cosechas y lotes', icono: 'agriculture', ruta: '/productor/produccion' },
    { label: 'Monitoreo', detalle: 'Clima y observaciones', icono: 'wb_cloudy', ruta: '/productor/monitoreo' },
    { label: 'Procesamiento', detalle: 'Etapas post-cosecha', icono: 'settings_suggest', ruta: null },
    { label: 'Calidad', detalle: 'Diagnóstico IA', icono: 'eco', ruta: null, badge: 1 },
    { label: 'Inventario', detalle: 'Stock por lote', icono: 'inventory_2', ruta: null },
    { label: 'Alertas', detalle: 'Plagas y avisos', icono: 'notifications_active', ruta: null, badge: 2 }
  ]
})

const notificaciones = ref([
  // Placeholder de ejemplo: cuando existan modulos reales de Calidad/Alertas,
  // esto se llenara desde el backend en vez de estar escrito a mano aqui.
])

function esRutaActual (ruta) {
  return !!ruta && route.path === ruta
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

async function cerrarSesion () {
  await auth.logout()
  router.push('/login')
}
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
