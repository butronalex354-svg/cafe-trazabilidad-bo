<template>
  <div class="login-wrapper window-height window-width" :class="{ revelado: mostrarLogin }">
    <AnimatedCafeBackground />

    <div class="hero-panel">
      <div class="hero-logo-wrap">
        <img :src="logoCafeAndino" alt="Café Andino" class="hero-logo" />
      </div>

      <h1 class="hero-title">Trazabilidad Inteligente<br>del Café Boliviano</h1>
      <p class="hero-subtitle">
        Desde la parcela hasta la exportación: cada lote queda registrado, verificado
        y disponible para consultar con un simple escaneo QR.
      </p>

      <div class="hero-features">
        <div class="feature-row">
          <div class="feature-icon"><q-icon name="qr_code_2" size="20px" /></div>
          <span>Trazabilidad completa por lote, desde la cosecha hasta la exportación</span>
        </div>
        <div class="feature-row">
          <div class="feature-icon"><q-icon name="eco" size="20px" /></div>
          <span>Diagnóstico de plagas y enfermedades con IA</span>
        </div>
        <div class="feature-row">
          <div class="feature-icon"><q-icon name="verified" size="20px" /></div>
          <span>Verificación técnica y control de calidad en cada etapa</span>
        </div>
      </div>

      <q-btn
        v-if="!mostrarLogin"
        unelevated
        no-caps
        label="Ingresar al sistema"
        icon-right="arrow_forward"
        class="cta-ingresar text-weight-bold q-mt-xl"
        @click="mostrarLogin = true"
      />
    </div>

    <Transition name="panel-reveal">
    <div v-if="mostrarLogin" class="form-panel flex flex-center">
    <div class="login-card">
      <div class="text-center q-mb-md">
        <div class="logo-frame">
          <span class="steam s1"></span>
          <span class="steam s2"></span>
          <span class="steam s3"></span>
          <img :src="logoCafeAndino" alt="Café Andino" class="brand-logo" />
        </div>
      </div>

      <q-form @submit.prevent="onSubmit" class="q-gutter-md q-mt-lg">
        <div>
          <div class="field-label">CORREO ELECTRÓNICO</div>
          <q-input
            v-model="email"
            type="email"
            placeholder="usuario@cafe.bo"
            dense
            borderless
            dark
            class="gold-input"
            :rules="[val => !!val || 'El correo es obligatorio']"
          />
        </div>

        <div>
          <div class="field-label">CONTRASEÑA</div>
          <q-input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Ingresa tu contraseña"
            dense
            borderless
            dark
            class="gold-input"
            :rules="[val => !!val || 'La contraseña es obligatoria']"
          >
            <template v-slot:append>
              <q-icon
                :name="showPassword ? 'visibility_off' : 'visibility'"
                color="amber-4"
                class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>
        </div>

        <div class="row items-center justify-between q-mt-sm">
          <q-checkbox v-model="recordar" label="Recordarme" dark color="amber" dense class="text-caption text-grey-4" />
          <div class="text-caption forgot-link cursor-pointer">¿Olvidaste tu contraseña?</div>
        </div>

        <div v-if="errorMsg" class="text-negative text-caption">{{ errorMsg }}</div>

        <q-btn
          type="submit"
          label="Ingresar al sistema"
          class="full-width text-weight-bold sign-in-btn"
          unelevated
          :loading="cargando"
        />

        <div class="flex flex-center q-mt-sm">
          <q-btn round flat type="button" class="google-icon-btn" @click="onGoogleClick">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" />
          </q-btn>
        </div>
      </q-form>

      <div class="text-center q-mt-md">
        <span class="text-caption text-grey-5">¿Nuevo aquí? </span>
        <span class="text-caption create-account-link cursor-pointer" @click="router.push('/registro')">Crear una cuenta</span>
      </div>
    </div>
    </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import logoCafeAndino from '@/assets/logo-cafe-andino.png'
import AnimatedCafeBackground from '@/components/AnimatedCafeBackground.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const $q = useQuasar()

function onGoogleClick () {
  // Navegacion de pagina completa (no axios): el backend redirige a Google
  // y, al volver, redirige a /auth/google/callback con el token.
  window.location.href = `http://${window.location.hostname}:8000/api/auth/google/redirect`
}

const MENSAJES_ERROR_GOOGLE = {
  pendiente: 'Tu cuenta todavía no fue aprobada por un administrador.',
  rechazado: 'Tu cuenta fue rechazada. Contacta al administrador.',
  desactivado: 'Tu cuenta fue desactivada por un administrador.',
  fallo: 'No se pudo iniciar sesión con Google. Intenta de nuevo.'
}

onMounted(() => {
  const error = route.query.google_error
  if (error) {
    errorMsg.value = MENSAJES_ERROR_GOOGLE[error] || 'No se pudo iniciar sesión con Google.'
  }
})

// Arranca oculto (vista tipo "landing") y se revela al apretar el boton.
// Si Google redirigio de vuelta con un error, se muestra directo el
// formulario para que el mensaje de error no quede escondido detras de un
// clic extra.
const mostrarLogin = ref(!!route.query.google_error)

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const recordar = ref(false)
const errorMsg = ref('')
const cargando = ref(false)

async function onSubmit () {
  errorMsg.value = ''
  cargando.value = true
  try {
    const user = await auth.login(email.value, password.value, recordar.value)
    router.push(`/${user.rol}`)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'No se pudo iniciar sesión.'
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.login-wrapper {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: row;
  background: radial-gradient(ellipse at 50% 0%, #241812 0%, #140d09 45%, #050302 100%);
  font-family: 'Poppins', sans-serif;
}

.form-panel {
  position: relative;
  z-index: 1;
  flex: 1 1 50%;
}

.hero-panel {
  position: relative;
  z-index: 1;
  flex: 1 1 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 9%;
  animation: heroEnter .8s cubic-bezier(.2, .8, .2, 1) both;
  transition: flex-basis .45s ease, padding .45s ease;
}
@keyframes heroEnter {
  from { opacity: 0; transform: translateX(-16px); }
  to { opacity: 1; transform: translateX(0); }
}

/* Antes de apretar "Ingresar al sistema" (vista tipo landing): el panel
   ocupa toda la pantalla y queda centrado, no pegado a la izquierda. Solo
   en pantalla ancha -- en celular el panel ya se ve centrado/apilado
   siempre (ver el media query mas abajo), no hace falta este estado aparte
   y si se aplicara aca tambien pisaria esas reglas (esta es mas especifica). */
@media (min-width: 1024px) {
  .login-wrapper:not(.revelado) .hero-panel {
    flex: 1 1 100%;
    align-items: center;
    text-align: center;
    padding: 0 12%;
  }
  .login-wrapper:not(.revelado) .hero-subtitle,
  .login-wrapper:not(.revelado) .hero-features {
    margin-left: auto;
    margin-right: auto;
  }
}

.cta-ingresar {
  align-self: center;
  background: linear-gradient(135deg, #d4a62f, #6a5318);
  color: #1a1108;
  height: 46px;
  padding: 0 28px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(212, 166, 47, 0.35);
  transition: transform .18s ease, box-shadow .18s ease;
}
.cta-ingresar:hover {
  transform: translateY(-2px) scale(1.015);
  box-shadow: 0 4px 26px rgba(212, 166, 47, 0.5);
}

/* Animacion de entrada de la tarjeta al revelarla (ademas de la propia
   animacion de .login-card, cardEnter, que ya se dispara sola al montarse). */
.panel-reveal-enter-active {
  transition: opacity .4s ease, transform .4s cubic-bezier(.2, .8, .2, 1);
}
.panel-reveal-enter-from {
  opacity: 0;
  transform: translateX(24px);
}

.hero-logo-wrap {
  position: relative;
  display: flex;
  justify-content: center;
  margin-bottom: 32px;
}
.hero-logo-wrap::before {
  content: '';
  position: absolute;
  inset: -20%;
  background: radial-gradient(circle, rgba(255, 214, 140, 0.3) 0%, rgba(212, 166, 47, 0.12) 50%, transparent 75%);
  filter: blur(18px);
  z-index: -1;
}

.hero-logo {
  width: 220px;
  max-width: 60%;
  filter: brightness(1.1) contrast(1.1) saturate(1.15) drop-shadow(0 0 18px rgba(255, 200, 120, 0.35));
}

.hero-title {
  font-family: 'Poppins', sans-serif;
  font-size: 2.4rem;
  line-height: 1.2;
  font-weight: 700;
  margin: 0 0 18px;
  max-width: 480px;
  background: linear-gradient(135deg, #fff6e8 20%, #d4a62f 85%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  color: #c9b79a;
  font-size: 1rem;
  line-height: 1.6;
  max-width: 420px;
  margin: 0 0 36px;
}

.hero-features {
  display: flex;
  flex-direction: column;
  gap: 18px;
  max-width: 420px;
}
.feature-row {
  display: flex;
  align-items: center;
  gap: 14px;
  color: #e8d9bf;
  font-size: .92rem;
  line-height: 1.4;
}
.feature-icon {
  flex: none;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f3dfa0;
  background: rgba(212, 166, 47, 0.1);
  border: 1px solid rgba(212, 166, 47, 0.25);
}

/* En celular ya no hay lugar para 2 columnas lado a lado: el panel pasa
   a mostrarse arriba, apilado, y la pantalla deja de ser de altura fija
   (window-height de Quasar) para poder hacer scroll si no entra todo. */
@media (max-width: 1023px) {
  .login-wrapper {
    flex-direction: column;
    /* Quasar le pone "height: 100vh !important" via la clase window-height
       del template -- hay que ganarle con !important tambien, si no el
       contenido apilado (panel + tarjeta) queda cortado sin poder hacer scroll. */
    height: auto !important;
    min-height: 100vh;
    overflow-y: auto;
  }
  .hero-panel {
    flex: 0 0 auto;
    align-items: center;
    text-align: center;
    padding: 48px 7% 24px;
  }
  .hero-subtitle {
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
  }
  .hero-features {
    max-width: 420px;
    margin: 0 auto;
    text-align: left;
  }
  .hero-logo {
    width: 150px;
  }
  .hero-title {
    font-size: 1.7rem;
  }
  .form-panel {
    flex: 0 0 auto;
    padding: 24px 0 48px;
  }
}

.login-card {
  position: relative;
  z-index: 1;
  width: 380px;
  max-width: 90vw;
  padding: 36px 32px;
  border-radius: 18px;
  background: rgba(20, 14, 10, 0.45);
  border: 1px solid rgba(212, 166, 47, 0.3);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 0 40px rgba(212, 166, 47, 0.12), 0 20px 60px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  font-family: 'Poppins', sans-serif;
  animation: cardEnter .7s cubic-bezier(.2, .8, .2, 1) both;
}
@keyframes cardEnter {
  from { opacity: 0; transform: translateY(22px) scale(.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.logo-frame {
  position: relative;
  display: inline-block;
  padding: 8px;
}
.logo-frame::before {
  content: '';
  position: absolute;
  inset: 14%;
  background: radial-gradient(circle, rgba(255, 214, 140, 0.28) 0%, rgba(212, 166, 47, 0.1) 50%, transparent 75%);
  filter: blur(8px);
  z-index: -1;
  border-radius: 50%;
}

.steam {
  position: absolute;
  top: 6%;
  width: 10px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(to top, rgba(255, 240, 220, 0) 0%, rgba(255, 240, 220, 0.35) 40%, rgba(255, 240, 220, 0) 100%);
  filter: blur(3px);
  pointer-events: none;
  animation: steamRise 4.5s ease-in infinite;
  opacity: 0;
}
.steam.s1 { left: 40%; animation-delay: 0s; }
.steam.s2 { left: 50%; animation-delay: 1.4s; height: 46px; }
.steam.s3 { left: 60%; animation-delay: 2.6s; }
@keyframes steamRise {
  0% { opacity: 0; transform: translateY(0) translateX(0) scaleX(1); }
  15% { opacity: .55; }
  50% { transform: translateY(-38px) translateX(-6px) scaleX(1.3); }
  85% { opacity: .12; }
  100% { opacity: 0; transform: translateY(-78px) translateX(6px) scaleX(1.6); }
}
.brand-logo {
  width: 230px;
  max-width: 76%;
  filter: brightness(1.1) contrast(1.1) saturate(1.15) drop-shadow(0 0 10px rgba(255, 200, 120, 0.28));
}

.field-label {
  font-size: 11px;
  letter-spacing: 1px;
  color: #c9a877;
  margin-bottom: 4px;
  font-weight: 600;
}

.gold-input :deep(.q-field__control) {
  background: rgba(212, 166, 47, 0.06) !important;
  border: 1px solid rgba(212, 166, 47, 0.3);
  border-radius: 8px;
  padding: 0 12px;
  height: 42px;
  overflow: hidden;
  transition: box-shadow .2s, border-color .2s;
}
.gold-input.q-field--focused :deep(.q-field__control) {
  border-color: rgba(212, 166, 47, 0.9);
  box-shadow: 0 0 12px rgba(212, 166, 47, 0.35);
}
.gold-input :deep(input) {
  color: #fff6e8 !important;
  caret-color: #f3dfa0;
}
.gold-input :deep(input::placeholder) {
  color: #8a7458;
}
.gold-input :deep(input:-webkit-autofill),
.gold-input :deep(input:-webkit-autofill:hover),
.gold-input :deep(input:-webkit-autofill:focus),
.gold-input :deep(input:-webkit-autofill:active) {
  -webkit-text-fill-color: #fff6e8 !important;
  box-shadow: 0 0 0 1000px #241a10 inset !important;
  -webkit-box-shadow: 0 0 0 1000px #241a10 inset !important;
  background-clip: padding-box !important;
  transition: background-color 999999s ease-in-out 0s;
}

.forgot-link {
  color: #c9962f;
}
.forgot-link:hover {
  text-decoration: underline;
}

.sign-in-btn {
  background: linear-gradient(135deg, #d4a62f, #6a5318);
  color: #1a1108;
  height: 46px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(212, 166, 47, 0.35);
  transition: transform .18s ease, box-shadow .18s ease;
}
.sign-in-btn:hover {
  transform: translateY(-2px) scale(1.015);
  box-shadow: 0 4px 26px rgba(212, 166, 47, 0.5);
}
.sign-in-btn:active {
  transform: translateY(0) scale(.98);
}

.google-icon-btn {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  width: 42px;
  height: 42px;
  transition: transform .18s ease, background .18s ease, border-color .18s ease;
}
.google-icon-btn:hover {
  transform: scale(1.08);
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(212, 166, 47, 0.4);
}

.create-account-link {
  color: #f3dfa0;
}
.create-account-link:hover {
  text-decoration: underline;
}
</style>
