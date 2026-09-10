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
          <div class="field-label">NOMBRE COMPLETO</div>
          <q-input
            v-model="nombre"
            type="text"
            placeholder="Tu nombre completo"
            dense
            borderless
            dark
            class="gold-input"
            :rules="[val => !!val || 'El nombre es obligatorio']"
          />
        </div>

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
            placeholder="Crea una contraseña"
            dense
            borderless
            dark
            class="gold-input"
            :rules="[val => (val && val.length >= 6) || 'Mínimo 6 caracteres']"
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

        <div>
          <div class="field-label">CONFIRMAR CONTRASEÑA</div>
          <q-input
            v-model="passwordConfirmation"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Repite tu contraseña"
            dense
            borderless
            dark
            class="gold-input"
            :rules="[val => val === password || 'Las contraseñas no coinciden']"
          />
        </div>

        <div v-if="errorMsg" class="text-negative text-caption">{{ errorMsg }}</div>

        <q-btn
          type="submit"
          label="Crear cuenta"
          class="full-width text-weight-bold sign-in-btn"
          unelevated
          :loading="cargando"
        />
      </q-form>

      <div class="text-center q-mt-md">
        <span class="text-caption text-grey-5">¿Ya tienes cuenta? </span>
        <span class="text-caption create-account-link cursor-pointer" @click="router.push('/login')">Inicia sesión</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import logoCafeAndino from '@/assets/logo-cafe-andino.png'
import AnimatedCafeBackground from '@/components/AnimatedCafeBackground.vue'

const router = useRouter()
const auth = useAuthStore()

const nombre = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const showPassword = ref(false)
const errorMsg = ref('')
const cargando = ref(false)

async function onSubmit () {
  errorMsg.value = ''
  if (password.value !== passwordConfirmation.value) {
    errorMsg.value = 'Las contraseñas no coinciden.'
    return
  }
  cargando.value = true
  try {
    const user = await auth.register(nombre.value, email.value, password.value, passwordConfirmation.value)
    router.push(`/${user.rol}`)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'No se pudo crear la cuenta.'
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
  width: 190px;
  max-width: 68%;
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

.create-account-link {
  color: #f0c878;
}
.create-account-link:hover {
  text-decoration: underline;
}
</style>
