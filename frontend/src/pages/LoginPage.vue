<template>
  <div class="login-wrapper window-height window-width flex flex-center">
    <AnimatedCafeBackground />

    <div class="login-card">
      <q-icon name="coffee" class="bean-watermark" />
      <div class="text-center q-mb-md">
        <div class="logo-frame">
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
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import logoCafeAndino from '@/assets/logo-cafe-andino.png'
import AnimatedCafeBackground from '@/components/AnimatedCafeBackground.vue'

const router = useRouter()
const auth = useAuthStore()
const $q = useQuasar()

function onGoogleClick () {
  $q.notify({ message: 'Registro con Google próximamente', color: 'grey-8' })
}

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
    const user = await auth.login(email.value, password.value)
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
  background: radial-gradient(ellipse at 50% 0%, #241812 0%, #140d09 45%, #050302 100%);
  font-family: 'Poppins', sans-serif;
}

.login-card {
  position: relative;
  z-index: 1;
  width: 380px;
  max-width: 90vw;
  padding: 36px 32px;
  border-radius: 18px;
  background: rgba(20, 14, 10, 0.45);
  border: 1px solid rgba(214, 158, 46, 0.3);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 0 40px rgba(214, 158, 46, 0.12), 0 20px 60px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  font-family: 'Poppins', sans-serif;
  animation: cardEnter .7s cubic-bezier(.2, .8, .2, 1) both;
}
@keyframes cardEnter {
  from { opacity: 0; transform: translateY(22px) scale(.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.bean-watermark {
  position: absolute;
  top: -18px;
  right: -18px;
  font-size: 130px;
  color: rgba(224, 168, 74, 0.06);
  transform: rotate(18deg);
  pointer-events: none;
  z-index: 0;
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
  background: radial-gradient(circle, rgba(255, 214, 140, 0.28) 0%, rgba(224, 168, 74, 0.1) 50%, transparent 75%);
  filter: blur(8px);
  z-index: -1;
  border-radius: 50%;
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
  background: rgba(224, 168, 74, 0.06) !important;
  border: 1px solid rgba(224, 168, 74, 0.3);
  border-radius: 8px;
  padding: 0 12px;
  height: 42px;
  overflow: hidden;
  transition: box-shadow .2s, border-color .2s;
}
.gold-input.q-field--focused :deep(.q-field__control) {
  border-color: rgba(224, 168, 74, 0.9);
  box-shadow: 0 0 12px rgba(224, 168, 74, 0.35);
}
.gold-input :deep(input) {
  color: #fff6e8 !important;
  caret-color: #f0c878;
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
  color: #d6a84a;
}
.forgot-link:hover {
  text-decoration: underline;
}

.sign-in-btn {
  background: linear-gradient(135deg, #e0a84a, #8a5a1e);
  color: #1a1108;
  height: 46px;
  border-radius: 10px;
  box-shadow: 0 0 20px rgba(224, 168, 74, 0.35);
  transition: transform .18s ease, box-shadow .18s ease;
}
.sign-in-btn:hover {
  transform: translateY(-2px) scale(1.015);
  box-shadow: 0 4px 26px rgba(224, 168, 74, 0.5);
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
  border-color: rgba(224, 168, 74, 0.4);
}

.create-account-link {
  color: #f0c878;
}
.create-account-link:hover {
  text-decoration: underline;
}
</style>
