<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import axios from 'axios'
import { ApiError, ventasApi } from '@/api/ventasApi'

const router = useRouter()
const correo = ref('')
const clave = ref('')
const enviando = ref(false)
const mensaje = ref<string | null>(null)
const error = ref<string | null>(null)

async function iniciarSesion() {
  error.value = null
  mensaje.value = null
  enviando.value = true

  try {
    // Limpia cualquier sesión previa en localhost antes de validar las credenciales nuevas.
    await axios.get('/index.php?r=login/salir', { withCredentials: true }).catch(() => {})

    const form = new URLSearchParams()
    form.append('correo', correo.value.trim())
    form.append('clave', clave.value)

    let credencialesInvalidas = false
    try {
      const res = await axios.post('/index.php?r=login/entrar', form, {
        withCredentials: true,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      })
      if (typeof res.data === 'string' && res.data.includes('Correo o contraseña incorrectos')) {
        credencialesInvalidas = true
      }
    } catch {
      // Si el backend redirige a URL_BASE (puerto 8000), el navegador puede abortar el redirect CORS,
      // pero la cookie de sesión PHP ya quedó establecida si el login fue exitoso.
    }

    if (credencialesInvalidas) {
      error.value = 'Correo o contraseña incorrectos.'
      return
    }

    const sesion = await ventasApi.sesion()
    if (sesion.autenticado) {
      mensaje.value = `Bienvenido, ${sesion.usuario.nombre}. Redirigiendo al panel...`
      await router.push('/')
    } else {
      error.value = 'No se pudo validar la sesión.'
    }
  } catch (err) {
    if (err instanceof ApiError && err.status === 403) {
      await axios.get('/index.php?r=login/salir', { withCredentials: true }).catch(() => {})
      error.value = 'Tu cuenta no tiene rol de vendedor o administrador para acceder a este panel.'
      return
    }
    error.value = 'Correo o contraseña incorrectos.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="login-contenedor">
    <section class="tarjeta login-tarjeta">
      <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.25rem">
        <img class="marca-logo" src="/assets/img/logo.svg" alt="" />
        <span class="marca">MOTO<b>CONS</b></span>
      </div>

      <p class="vp-antetitulo">Autenticación</p>
      <h1>Iniciar sesión</h1>
      <p class="vp-subtitulo" style="margin-bottom: 1.5rem">
        Ingresa con tu cuenta de vendedor o administrador para acceder al panel de Ventas en tienda.
      </p>

      <div v-if="mensaje" class="alerta alerta-exito">{{ mensaje }}</div>
      <div v-if="error" class="alerta alerta-error">{{ error }}</div>

      <form @submit.prevent="iniciarSesion">
        <div class="campo">
          <label for="login-correo">Correo electrónico</label>
          <input
            id="login-correo"
            v-model="correo"
            type="email"
            placeholder="admin@motocons.com"
            autocomplete="username"
            required
          />
        </div>

        <div class="campo">
          <label for="login-clave">Contraseña</label>
          <input
            id="login-clave"
            v-model="clave"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>

        <button type="submit" class="btn btn-bloque" :disabled="enviando">
          {{ enviando ? 'Validando...' : 'Ingresar' }}
        </button>
      </form>

      <p class="vp-nota" style="margin-top: 1.25rem; text-align: center">
        <RouterLink to="/">Ir al Dashboard</RouterLink> ·
        <a href="/index.php?r=login/index">Abrir login PHP</a>
      </p>
    </section>
  </div>
</template>
