<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import axios from 'axios'
import { ApiError, ventasApi } from '@/api/ventasApi'
import type { UsuarioSesion } from '@/types/ventas'
import AdminSidebar from '@/components/AdminSidebar.vue'

const router = useRouter()
const cargandoSesion = ref(true)
const usuario = ref<UsuarioSesion | null>(null)
const errorAcceso = ref<string | null>(null)
const requiereLogin = ref(false)

async function cerrarSesion() {
  await axios.get('/index.php?r=login/salir', { withCredentials: true }).catch(() => {})
  usuario.value = null
  requiereLogin.value = true
  await router.push('/login')
}

async function verificarSesion() {
  cargandoSesion.value = true
  errorAcceso.value = null
  requiereLogin.value = false
  try {
    const res = await ventasApi.sesion()
    usuario.value = res.usuario
  } catch (err) {
    usuario.value = null
    if (err instanceof ApiError && err.status === 401) {
      requiereLogin.value = true
    } else if (err instanceof ApiError && err.status === 403) {
      errorAcceso.value = err.message
    } else {
      errorAcceso.value =
        err instanceof Error ? err.message : 'No se pudo conectar con el servidor PHP.'
    }
  } finally {
    cargandoSesion.value = false
  }
}

onMounted(() => {
  void verificarSesion()
})
</script>

<template>
  <header class="barra">
    <RouterLink class="marca" to="/">
      <img class="marca-logo" src="/assets/img/logo.svg" alt="" />
      <span>MOTO<b>CONS</b></span>
    </RouterLink>
    <nav>
      <template v-if="usuario">
        <span class="barra-usuario">{{ usuario.nombre }} ({{ usuario.rol }})</span>
        <button type="button" class="btn btn-secundario btn-chico" @click="cerrarSesion">
          Salir
        </button>
      </template>
      <RouterLink v-else class="btn btn-chico" to="/login">Iniciar sesión</RouterLink>
    </nav>
  </header>

  <div class="admin-shell">
    <AdminSidebar />

    <main class="contenedor">
      <div v-if="cargandoSesion" class="tarjeta">
        <div class="vp-skeleton"></div>
      </div>

      <section v-else-if="requiereLogin" class="tarjeta">
        <p class="vp-antetitulo">Acceso requerido</p>
        <h1>Inicia sesión en MOTOCONS</h1>
        <p class="vp-subtitulo">
          El módulo de Ventas en tienda requiere una sesión activa de vendedor o administrador.
        </p>
        <div class="vp-botones" style="margin-top: 1.25rem">
          <RouterLink class="btn" to="/login">Ir a la vista de Login</RouterLink>
          <a class="btn btn-secundario" href="/index.php?r=login/index">Iniciar sesión en PHP</a>
          <button type="button" class="btn btn-secundario" @click="verificarSesion">
            Verificar sesión
          </button>
        </div>
      </section>

      <div v-else-if="errorAcceso" class="alerta alerta-error">{{ errorAcceso }}</div>

      <RouterView v-else />
    </main>
  </div>

  <footer class="pie">MOTOCONS · Diplomado en Desarrollo Web en la Nube</footer>
</template>
