<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ApiError, ventasApi } from '@/api/ventasApi'
import type { EstadoVenta, VentaResumen } from '@/types/ventas'
import VentasTabla from '@/components/VentasTabla.vue'

const router = useRouter()

const cargando = ref(true)
const error = ref<string | null>(null)
const ventas = ref<VentaResumen[]>([])
const conteo = ref<Record<string, number>>({})
const estados = ref<Record<EstadoVenta, string>>({
  PENDIENTE: 'Pendiente',
  EN_CREDITO: 'En crédito',
  PAGADA: 'Pagada',
  ENTREGADA: 'Entregada',
  CERRADA: 'Cerrada',
  ANULADA: 'Anulada',
})
const estadoActivo = ref<string>('')
const busqueda = ref('')

async function cargar() {
  cargando.value = true
  error.value = null
  try {
    const data = await ventasApi.listar(busqueda.value, estadoActivo.value)
    ventas.value = data.ventas
    conteo.value = data.conteo
    estados.value = data.estados
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudieron cargar las ventas.'
  } finally {
    cargando.value = false
  }
}

function filtrarEstado(estado: string) {
  estadoActivo.value = estado
  void cargar()
}

function limpiarBusqueda() {
  busqueda.value = ''
  void cargar()
}

onMounted(() => {
  void cargar()
})
</script>

<template>
  <header class="vp-cabecera">
    <div>
      <p class="vp-antetitulo">Punto de venta</p>
      <h1>Ventas en tienda</h1>
      <p class="vp-subtitulo">
        Registra la venta, confirma el pago y la moto queda lista para garantía y taller.
      </p>
    </div>
    <RouterLink class="btn" to="/ventas/nueva">Nueva venta</RouterLink>
  </header>

  <div v-if="error" class="alerta alerta-error">{{ error }}</div>

  <VentasTabla
    v-model:busqueda="busqueda"
    :ventas="ventas"
    :conteo="conteo"
    :estados="estados"
    :estado-activo="estadoActivo"
    :cargando="cargando"
    @filtrar-estado="filtrarEstado"
    @buscar="cargar"
    @limpiar="limpiarBusqueda"
  />
</template>
