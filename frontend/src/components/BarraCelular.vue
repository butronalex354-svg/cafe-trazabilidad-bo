<template>
  <!-- Celular y tablet: barra flotante abajo, como una app. La pestaña activa sube con una curva dorada que brilla. -->
  <q-footer class="cafe-pie">
    <nav
      class="barra"
      aria-label="Menú principal"
      :style="{ '--n': atajos.length + 1, '--i': activo }"
    >
      <!-- La "joroba" que se desliza hasta la pestaña activa -->
      <span class="barra__joroba" aria-hidden="true">
        <svg viewBox="0 0 120 28" preserveAspectRatio="none">
          <defs>
            <linearGradient id="cafe-joroba-borde" x1="0" x2="1" y1="0" y2="0">
              <stop offset="0" class="joroba__stop" stop-opacity="0" />
              <stop offset="0.28" class="joroba__stop" />
              <stop offset="0.72" class="joroba__stop" />
              <stop offset="1" class="joroba__stop" stop-opacity="0" />
            </linearGradient>
          </defs>
          <path class="joroba__fondo" d="M0 28 C20 28 28 3 60 3 C92 3 100 28 120 28 Z" />
          <!-- La linea va un poco por dentro de la curva oscura: sobre el fondo dorado
               de las paginas, una linea dorada por fuera no se notaria -->
          <path
            class="joroba__borde"
            d="M0 28.4 C20 28.4 28 4.2 60 4.2 C92 4.2 100 28.4 120 28.4"
            stroke="url(#cafe-joroba-borde)"
          />
        </svg>
      </span>

      <button
        v-for="(item, i) in atajos"
        :key="item.ruta"
        type="button"
        class="pestana"
        :class="{ 'pestana--activa': activo === i }"
        :aria-current="activo === i ? 'page' : undefined"
        @click="ir(item)"
      >
        <span class="pestana__icono">
          <q-icon :name="item.icono" size="22px" />
          <span v-if="item.badge" class="pestana__punto" :aria-label="`${item.badge} pendientes`" />
        </span>
        <span class="pestana__texto">{{ item.corto || item.label }}</span>
      </button>

      <button
        type="button"
        class="pestana"
        :class="{ 'pestana--activa': activo === atajos.length }"
        :aria-expanded="hoja"
        aria-haspopup="dialog"
        @click="hoja = true"
      >
        <span class="pestana__icono">
          <q-icon name="apps" size="22px" />
          <span v-if="pendientesEnMas" class="pestana__punto" aria-hidden="true" />
        </span>
        <span class="pestana__texto">Más</span>
      </button>
    </nav>

    <!-- "Más": todas las demás secciones del rol -->
    <q-dialog v-model="hoja" position="bottom">
      <div class="hoja" role="dialog" aria-label="Todas las secciones">
        <span class="hoja__asa" aria-hidden="true" />
        <div class="hoja__cabecera">
          <div>
            <div class="hoja__titulo">Todas las secciones</div>
            <div class="hoja__usuario">{{ nombre }} · {{ rol }}</div>
          </div>
          <button v-close-popup type="button" class="hoja__cerrar" aria-label="Cerrar">
            <q-icon name="close" size="20px" />
          </button>
        </div>

        <div class="hoja__rejilla">
          <button
            v-for="item in itemsMas"
            :key="item.ruta"
            v-close-popup
            type="button"
            class="hoja__item"
            :class="{ 'hoja__item--activo': esActualExacto(item.ruta) }"
            :aria-current="esActualExacto(item.ruta) ? 'page' : undefined"
            @click="ir(item)"
          >
            <q-icon :name="item.icono" size="22px" />
            <span>{{ item.label }}</span>
            <span v-if="item.badge" class="hoja__contador">{{ item.badge }}</span>
          </button>
        </div>

        <button v-close-popup type="button" class="hoja__salir" @click="emit('salir')">
          <q-icon name="logout" size="20px" /> Cerrar sesión
        </button>
      </div>
    </q-dialog>
  </q-footer>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const props = defineProps({
  // Todas las secciones del rol: { label, corto?, icono, ruta, badge? }
  items: { type: Array, required: true },
  // Las rutas que van fijas en la barra (hasta 4); el resto va en "Más"
  rutasAtajos: { type: Array, required: true },
  nombre: { type: String, default: '' },
  rol: { type: String, default: '' }
})
const emit = defineEmits(['salir'])
const route = useRoute()
const router = useRouter()

const hoja = ref(false)

const atajos = computed(() =>
  props.rutasAtajos.map(r => props.items.find(i => i.ruta === r)).filter(Boolean)
)
const itemsMas = computed(() => props.items.filter(i => !props.rutasAtajos.includes(i.ruta)))

// La ruta puede traer un query (ej. "/productor/calidad?seccion=alertas"): se compara solo el path
function esActual (ruta) {
  return !!ruta && route.path === ruta.split('?')[0]
}

// En la hoja "Más", una seccion con query (Alertas = Calidad con ?seccion=alertas)
// solo se marca si coincide entera, para no marcar Alertas estando en Calidad
function esActualExacto (ruta) {
  if (!ruta) return false
  return ruta.includes('?') ? route.fullPath === ruta : esActual(ruta)
}

// Pestaña activa: la del atajo, o "Más" si se está en otra sección
const activo = computed(() => {
  const i = atajos.value.findIndex(a => esActual(a.ruta))
  return i === -1 ? atajos.value.length : i
})

const pendientesEnMas = computed(() => itemsMas.value.some(i => i.badge))

function ir (item) {
  if (item.ruta && route.fullPath !== item.ruta) router.push(item.ruta)
}
</script>

<style scoped>
/* El pie de Quasar es transparente: lo que se ve es la barra flotante */
.cafe-pie {
  padding: 0 12px calc(12px + env(safe-area-inset-bottom));
  overflow: visible;
  background: transparent;
  box-shadow: none;
}

.barra {
  /* Mismos colores del tema del sistema (css/app.scss): fondo café oscuro y dorado */
  --fondo: color-mix(in srgb, var(--color-fondo-oscuro, #2a1f18) 95%, transparent);
  --brillo: var(--color-primario, #d4a62f);

  position: relative;
  display: grid;
  grid-template-columns: repeat(var(--n), minmax(0, 1fr));
  max-width: 560px;
  height: 66px;
  margin: 0 auto;
  padding: 0 12px;
  border: 1px solid rgb(255 255 255 / 0.09);
  border-radius: 24px;
  background: var(--fondo);
  box-shadow:
    inset 0 1px 0 rgb(255 255 255 / 0.05),
    0 14px 34px -12px rgb(0 0 0 / 0.7),
    0 10px 28px -18px color-mix(in srgb, var(--brillo) 60%, transparent);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

/* ---------- La curva del activo ---------- */
.barra__joroba {
  position: absolute;
  top: -27px;
  left: 12px; /* el mismo relleno de la barra */
  width: calc((100% - 24px) / var(--n));
  height: 28px;
  pointer-events: none;
  transform: translateX(calc(var(--i) * 100%));
  transition: transform 420ms cubic-bezier(0.22, 1, 0.36, 1);
}

.barra__joroba svg {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 100px;
  height: 28px;
  overflow: visible;
  transform: translateX(-50%);
}

.joroba__stop {
  stop-color: var(--color-primario-claro, #f3dfa0);
}

.joroba__fondo {
  fill: var(--fondo);
}

.joroba__borde {
  fill: none;
  stroke-width: 2;
  filter: drop-shadow(0 0 3px var(--brillo)) drop-shadow(0 0 6px color-mix(in srgb, var(--brillo) 70%, transparent));
}

/* ---------- Pestañas ---------- */
.pestana {
  position: relative;
  display: grid;
  align-content: center;
  justify-items: center;
  gap: 4px;
  min-width: 0;
  padding: 0 2px;
  border: 0;
  background: transparent;
  color: #b5a48c;
  font: inherit;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.pestana__icono {
  position: relative;
  display: grid;
  place-items: center;
  transition:
    transform 420ms cubic-bezier(0.22, 1, 0.36, 1),
    color 200ms ease;
}

.pestana__texto {
  max-width: 100%;
  overflow: hidden;
  font-size: clamp(0.62rem, 2.75vw, 0.72rem);
  font-weight: 600;
  letter-spacing: -0.01em;
  white-space: nowrap;
  text-overflow: ellipsis;
  transition: color 200ms ease;
}

/* Activo: el ícono sube dentro de la curva y se ilumina */
.pestana--activa {
  color: #fff;
}

.pestana--activa .pestana__icono {
  color: var(--brillo);
  filter: drop-shadow(0 0 6px color-mix(in srgb, var(--brillo) 75%, transparent));
  transform: translateY(-13px);
}

/* Un puntito de luz entre el ícono y el nombre */
.pestana--activa::after {
  position: absolute;
  top: 27px;
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: var(--brillo);
  box-shadow: 0 0 6px color-mix(in srgb, var(--brillo) 90%, transparent);
  content: '';
}

/* Aviso de pendientes */
.pestana__punto {
  position: absolute;
  top: -2px;
  right: -4px;
  width: 9px;
  height: 9px;
  border: 2px solid var(--color-fondo-oscuro, #2a1f18);
  border-radius: 999px;
  background: #ff6b57;
  box-sizing: content-box;
}

.pestana:focus-visible {
  border-radius: 16px;
  outline: 2px solid var(--brillo);
  outline-offset: -4px;
}

/* ---------- Hoja "Más" ---------- */
.hoja {
  --brillo: var(--color-primario, #d4a62f);

  display: grid;
  gap: 18px;
  width: 100vw;
  max-width: 640px;
  max-height: 82vh;
  padding: 10px 18px calc(20px + env(safe-area-inset-bottom));
  overflow-y: auto;
  border: 1px solid rgb(255 255 255 / 0.08);
  border-bottom: 0;
  border-radius: 26px 26px 0 0;
  background: var(--gradiente-oscuro, #2a1f18);
  color: #f5ead8;
  box-shadow: 0 -12px 40px -10px rgb(0 0 0 / 0.6);
}

.hoja__asa {
  justify-self: center;
  width: 42px;
  height: 4px;
  border-radius: 999px;
  background: rgb(255 255 255 / 0.18);
}

.hoja__cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.hoja__titulo {
  font-size: 1.05rem;
  font-weight: 700;
}

.hoja__usuario {
  margin-top: 2px;
  color: #b5a48c;
  font-size: 0.8rem;
}

.hoja__cerrar {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border: 1px solid rgb(255 255 255 / 0.1);
  border-radius: 12px;
  background: transparent;
  color: #f5ead8;
  cursor: pointer;
}

.hoja__rejilla {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

@media (min-width: 600px) {
  .hoja__rejilla {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.hoja__item {
  position: relative;
  display: grid;
  gap: 8px;
  justify-items: center;
  align-content: center;
  min-height: 80px;
  padding: 12px 6px;
  border: 1px solid rgb(255 255 255 / 0.07);
  border-radius: 18px;
  background: rgb(255 255 255 / 0.03);
  color: #f5ead8;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.2;
  text-align: center;
  cursor: pointer;
}

.hoja__item .q-icon {
  color: var(--brillo);
}

.hoja__item--activo {
  border-color: color-mix(in srgb, var(--brillo) 45%, transparent);
  background: color-mix(in srgb, var(--brillo) 14%, transparent);
}

.hoja__contador {
  position: absolute;
  top: 8px;
  right: 8px;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 999px;
  background: #ff6b57;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 800;
  line-height: 20px;
}

.hoja__salir {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  border: 1px solid rgb(255 255 255 / 0.09);
  border-radius: 16px;
  background: transparent;
  color: #ff8a78;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.hoja__item:focus-visible,
.hoja__salir:focus-visible,
.hoja__cerrar:focus-visible {
  outline: 2px solid var(--brillo);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .barra__joroba,
  .pestana__icono {
    transition: none;
  }
}
</style>
