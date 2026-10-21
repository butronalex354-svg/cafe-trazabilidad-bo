<template>
  <q-page class="q-pa-md pagina-tema">
    <div class="text-h5 text-brown-9 q-mb-xs">Auditoría</div>
    <div class="text-caption text-grey-7 q-mb-md">Historial de quién modificó qué y cuándo (cuentas de usuario)</div>

    <q-inner-loading :showing="admin.cargando" />

    <div v-if="!admin.cargando && admin.auditoria.length === 0" class="text-grey-7 q-pa-md text-center caja-tema rounded-borders">
      Todavía no hay registros de auditoría.
    </div>

    <q-list v-else bordered separator class="rounded-borders caja-tema">
      <q-item v-for="r in admin.auditoria" :key="r.id">
        <q-item-section avatar>
          <q-icon :name="iconoAccion(r.accion)" :color="colorAccion(r.accion)" size="26px" />
        </q-item-section>
        <q-item-section>
          <q-item-label>{{ r.descripcion }}</q-item-label>
          <q-item-label caption>
            Por: {{ r.usuario?.nombre || 'Sistema' }}
            <template v-if="r.usuario?.email"> ({{ r.usuario.email }})</template>
            · {{ formatearFecha(r.created_at) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <q-badge color="brown-7" :label="etiquetaAccion(r.accion)" />
        </q-item-section>
      </q-item>
    </q-list>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAdministradorStore } from '@/stores/administrador'

const admin = useAdministradorStore()

function etiquetaAccion (accion) {
  return {
    crear_usuario: 'Cuenta creada',
    aprobar_usuario: 'Cuenta aprobada',
    rechazar_usuario: 'Cuenta rechazada',
    desactivar_usuario: 'Cuenta desactivada',
    reactivar_usuario: 'Cuenta reactivada',
    editar_cuenta: 'Datos editados'
  }[accion] || accion
}

function iconoAccion (accion) {
  return {
    crear_usuario: 'person_add',
    aprobar_usuario: 'check_circle',
    rechazar_usuario: 'cancel',
    desactivar_usuario: 'block',
    reactivar_usuario: 'check',
    editar_cuenta: 'edit'
  }[accion] || 'history'
}

function colorAccion (accion) {
  return {
    crear_usuario: 'primary',
    aprobar_usuario: 'positive',
    rechazar_usuario: 'negative',
    desactivar_usuario: 'grey-7',
    reactivar_usuario: 'positive',
    editar_cuenta: 'amber-8'
  }[accion] || 'grey'
}

function formatearFecha (fecha) {
  if (!fecha) return ''
  const f = new Date(fecha)
  return f.toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  admin.cargarAuditoria()
})
</script>
