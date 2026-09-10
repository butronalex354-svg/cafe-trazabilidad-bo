<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Dashboard del {{ etiquetaRol }}</div>
    <q-banner class="bg-green-1 text-green-10" rounded>
      Bienvenido, {{ auth.user?.nombre }}. Iniciaste sesión correctamente como <b>{{ auth.user?.rol }}</b>.
    </q-banner>

    <q-btn
      label="Cerrar sesión"
      color="negative"
      outline
      class="q-mt-lg"
      @click="cerrarSesion"
    />
  </q-page>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const etiquetaRol = computed(() => {
  const nombres = { productor: 'Productor', verificador: 'Verificador', administrador: 'Administrador' }
  return nombres[auth.rol] || ''
})

async function cerrarSesion () {
  await auth.logout()
  router.push('/login')
}
</script>
