<template>
  <div class="callback-wrapper window-height window-width flex flex-center">
    <q-spinner-dots color="amber" size="3em" />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

onMounted(async () => {
  const token = route.query.token

  if (!token) {
    router.replace('/login')
    return
  }

  try {
    const user = await auth.loginConToken(token)
    router.replace(`/${user.rol}`)
  } catch {
    router.replace('/login')
  }
})
</script>

<style scoped>
.callback-wrapper {
  background: radial-gradient(ellipse at 50% 0%, #241812 0%, #140d09 45%, #050302 100%);
}
</style>
