<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ApiError, ventasApi } from '@/api/ventasApi'
import type { EstadoVenta, VentaDetalle } from '@/types/ventas'
import VentaEditarForm from '@/components/VentaEditarForm.vue'

const route = useRoute()
const router = useRouter()

const cargando = ref(true)
const procesando = ref(false)
const error = ref<string | null>(null)
const mensaje = ref<string | null>(null)

const venta = ref<VentaDetalle | null>(null)
const estados = ref<Record<EstadoVenta, string>>({
  PENDIENTE: 'Pendiente',
  EN_CREDITO: 'En crédito',
  PAGADA: 'Pagada',
  ENTREGADA: 'Entregada',
  CERRADA: 'Cerrada',
  ANULADA: 'Anulada',
})
const acciones = ref<Partial<Record<EstadoVenta, string>>>({})
const token = ref('')

async function cargar() {
  cargando.value = true
  error.value = null
  const id = Number(route.params.id)
  try {
    const data = await ventasApi.ver(id)
    venta.value = data.venta
    estados.value = data.estados
    acciones.value = data.acciones
    token.value = data.token
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo cargar la venta.'
  } finally {
    cargando.value = false
  }
}

async function actualizarEstado(nuevoEstado: EstadoVenta) {
  if (!venta.value) return
  procesando.value = true
  error.value = null
  mensaje.value = null
  try {
    const res = await ventasApi.cambiarEstado(venta.value.id, nuevoEstado, token.value)
    venta.value = res.venta
    acciones.value = res.acciones
    mensaje.value = res.mensaje
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo actualizar la venta.'
  } finally {
    procesando.value = false
  }
}

onMounted(() => {
  void cargar()
})
</script>

<template>
  <header class="vp-cabecera">
    <div>
      <p class="vp-antetitulo">Actualizar venta</p>
      <h1>Editar venta N.º {{ route.params.id }}</h1>
      <p class="vp-subtitulo">Actualiza el estado de la venta según el flujo del concesionario.</p>
    </div>
    <div class="vp-botones">
      <RouterLink class="btn btn-secundario" :to="`/ventas/${route.params.id}`">
        Ver comprobante
      </RouterLink>
      <RouterLink class="btn btn-secundario" to="/ventas">Volver al listado</RouterLink>
    </div>
  </header>

  <div v-if="mensaje" class="alerta alerta-exito">{{ mensaje }}</div>
  <div v-if="error" class="alerta alerta-error">{{ error }}</div>

  <div v-if="cargando" class="tarjeta">
    <div class="vp-skeleton"></div>
  </div>

  <VentaEditarForm
    v-else-if="venta"
    :venta="venta"
    :estados="estados"
    :acciones="acciones"
    :procesando="procesando"
    @actualizar="actualizarEstado"
    @cancelar="router.push(`/ventas/${venta.id}`)"
  />
</template>
