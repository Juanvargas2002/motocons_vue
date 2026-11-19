<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ApiError, ventasApi } from '@/api/ventasApi'
import type { EstadoVenta, VentaDetalle } from '@/types/ventas'
import { formatoFecha } from '@/utils/formato'
import VentaDetalleCard from '@/components/VentaDetalleCard.vue'

const route = useRoute()
const router = useRouter()

const cargando = ref(true)
const procesando = ref(false)
const noEncontrada = ref(false)
const error = ref<string | null>(null)
const mensaje = ref<string | null>(
  typeof route.query.mensaje === 'string' ? route.query.mensaje : null,
)

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
  noEncontrada.value = false
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
    if (err instanceof ApiError && err.status === 404) {
      noEncontrada.value = true
      venta.value = null
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo cargar la venta.'
  } finally {
    cargando.value = false
  }
}

async function cambiarEstado(destino: EstadoVenta) {
  if (!venta.value) return
  procesando.value = true
  error.value = null
  mensaje.value = null
  try {
    const res = await ventasApi.cambiarEstado(venta.value.id, destino, token.value)
    venta.value = res.venta
    acciones.value = res.acciones
    mensaje.value = res.mensaje
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo cambiar el estado.'
  } finally {
    procesando.value = false
  }
}

function imprimir() {
  window.print()
}

watch(
  () => route.params.id,
  () => {
    void cargar()
  },
)

onMounted(() => {
  void cargar()
})
</script>

<template>
  <div v-if="cargando" class="tarjeta">
    <div class="vp-skeleton"></div>
  </div>

  <template v-else-if="noEncontrada || !venta">
    <header class="vp-cabecera">
      <div>
        <p class="vp-antetitulo">Punto de venta</p>
        <h1>Venta no encontrada</h1>
      </div>
      <RouterLink class="btn btn-secundario" to="/ventas">Volver al listado</RouterLink>
    </header>
    <div class="alerta alerta-error">
      No encontramos esa venta en tienda. Revisa el enlace o búscala en el listado.
    </div>
  </template>

  <template v-else>
    <header class="vp-cabecera">
      <div>
        <p class="vp-antetitulo">Venta en tienda · N.º {{ venta.id }}</p>
        <h1>
          {{ venta.numero_factura !== null ? `Factura ${venta.numero_factura}` : 'Venta sin factura' }}
        </h1>
        <p class="vp-subtitulo">
          {{ formatoFecha(venta.fecha) }} · Vendedor: {{ venta.vendedor ?? 'sin registro' }}
        </p>
      </div>
      <div class="vp-botones">
        <button type="button" class="btn btn-secundario" @click="imprimir">Imprimir</button>
        <RouterLink class="btn btn-secundario" :to="`/ventas/${venta.id}/editar`">
          Editar
        </RouterLink>
        <RouterLink class="btn btn-secundario" to="/ventas">Volver al listado</RouterLink>
      </div>
    </header>

    <div v-if="mensaje" class="alerta alerta-exito">{{ mensaje }}</div>
    <div v-if="error" class="alerta alerta-error">{{ error }}</div>

    <VentaDetalleCard
      :venta="venta"
      :estados="estados"
      :acciones="acciones"
      :procesando="procesando"
      @cambiar-estado="cambiarEstado"
    />
  </template>
</template>
