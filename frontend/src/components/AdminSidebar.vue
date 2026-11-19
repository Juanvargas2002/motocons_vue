<script setup lang="ts">
import { RouterLink } from 'vue-router'

const modulosBloqueados = [
  { nombre: 'Créditos y financiación' },
  { nombre: 'Taller y mantenimientos' },
  { nombre: 'Garantía' },
  { nombre: 'Inventario' },
  { nombre: 'Promociones' },
]
</script>

<template>
  <aside class="admin-sidebar" aria-label="Navegación del panel administrativo">
    <div class="admin-sidebar-grupo">
      <p class="admin-sidebar-titulo">Panel</p>
      <RouterLink class="admin-nav-link" to="/" exact-active-class="router-link-active">
        <span>Dashboard</span>
      </RouterLink>
    </div>

    <div class="admin-sidebar-grupo">
      <p class="admin-sidebar-titulo">Módulo activo</p>
      <RouterLink class="admin-nav-link" to="/ventas">
        <span>Ventas en tienda</span>
      </RouterLink>
      <RouterLink class="admin-nav-link" to="/ventas/nueva">
        <span>Nueva venta</span>
      </RouterLink>
    </div>

    <div class="admin-sidebar-grupo">
      <p class="admin-sidebar-titulo">Otros módulos</p>
      <div
        v-for="mod in modulosBloqueados"
        :key="mod.nombre"
        class="admin-nav-bloqueado"
        aria-disabled="true"
        title="Módulo no habilitado en esta vista"
      >
        <span>{{ mod.nombre }}</span>
        <span class="admin-nav-badge">Bloqueado</span>
      </div>
    </div>

    <div class="admin-sidebar-grupo">
      <p class="admin-sidebar-titulo">Cuenta</p>
      <RouterLink class="admin-nav-link" to="/login">
        <span>Autenticación</span>
      </RouterLink>
    </div>
  </aside>
</template>
