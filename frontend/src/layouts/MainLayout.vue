<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-brown-8">
      <q-toolbar>
        <q-icon name="local_cafe" size="28px" class="q-mr-sm" />
        <q-toolbar-title>CaféTrace Bolivia</q-toolbar-title>
        <div v-if="auth.user" class="text-caption">{{ auth.user.nombre }} · {{ auth.user.rol }}</div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>
