<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-brown-9 q-mb-xs">Catálogos del sistema</div>
        <div class="text-caption text-grey-7">Variedades de café disponibles para elegir al registrar una cosecha</div>
      </div>
      <q-btn unelevated no-caps color="brown-8" icon="add" label="Nueva variedad" @click="abrirNueva" />
    </div>

    <q-inner-loading :showing="cargando" />

    <q-list bordered separator class="rounded-borders caja-tema">
      <q-item v-for="v in variedades" :key="v.id">
        <q-item-section avatar>
          <q-icon name="local_cafe" :color="v.activo ? 'brown-7' : 'grey-6'" />
        </q-item-section>
        <q-item-section>
          <q-item-label :class="{ 'text-grey-6': !v.activo }">{{ v.nombre }}</q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="row q-gutter-sm items-center">
            <q-badge :color="v.activo ? 'positive' : 'grey-7'" :label="v.activo ? 'Activa' : 'Inactiva'" />
            <q-btn dense flat round icon="edit" color="brown-7" @click="abrirEditar(v)" />
            <q-btn
              dense flat round
              :icon="v.activo ? 'visibility_off' : 'visibility'"
              :color="v.activo ? 'negative' : 'positive'"
              @click="alternarActivo(v)"
            />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <q-dialog v-model="dlg">
      <q-card class="caja-tema" style="min-width: 300px; max-width: 380px; width: 100%">
        <q-card-section>
          <div class="text-h6 text-brown-9">{{ editando ? 'Editar variedad' : 'Nueva variedad' }}</div>
        </q-card-section>
        <q-card-section>
          <q-input v-model="form.nombre" label="Nombre de la variedad" dense outlined autofocus />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" no-caps v-close-popup />
          <q-btn unelevated color="brown-8" label="Guardar" no-caps :loading="guardando" @click="guardar" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()
const variedades = ref([])
const cargando = ref(true)

async function cargar () {
  cargando.value = true
  try {
    const { data } = await api.get('/catalogos/variedades-cafe', { params: { todas: 1 } })
    variedades.value = data
  } finally {
    cargando.value = false
  }
}

const dlg = ref(false)
const editando = ref(null)
const guardando = ref(false)
const form = ref({ nombre: '' })

function abrirNueva () {
  editando.value = null
  form.value = { nombre: '' }
  dlg.value = true
}

function abrirEditar (v) {
  editando.value = v
  form.value = { nombre: v.nombre }
  dlg.value = true
}

async function guardar () {
  if (!form.value.nombre) {
    $q.notify({ type: 'negative', message: 'Escribe el nombre de la variedad.' })
    return
  }
  guardando.value = true
  try {
    if (editando.value) {
      await api.put(`/administrador/catalogos/variedades-cafe/${editando.value.id}`, { nombre: form.value.nombre, activo: editando.value.activo })
    } else {
      await api.post('/administrador/catalogos/variedades-cafe', { nombre: form.value.nombre })
    }
    $q.notify({ type: 'positive', message: 'Variedad guardada.' })
    dlg.value = false
    await cargar()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo guardar.' })
  } finally {
    guardando.value = false
  }
}

async function alternarActivo (v) {
  try {
    await api.put(`/administrador/catalogos/variedades-cafe/${v.id}`, { nombre: v.nombre, activo: !v.activo })
    await cargar()
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'No se pudo actualizar.' })
  }
}

onMounted(cargar)
</script>
