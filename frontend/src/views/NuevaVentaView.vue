<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ApiError, ventasApi } from '@/api/ventasApi'
import type { MotoCatalogo, RegistrarVentaPayload } from '@/types/ventas'
import VentaCrearForm from '@/components/VentaCrearForm.vue'

const router = useRouter()
const formRef = ref<InstanceType<typeof VentaCrearForm> | null>(null)

const cargandoCatalogo = ref(true)
const enviando = ref(false)
const buscandoCliente = ref(false)
const error = ref<string | null>(null)

const catalogo = ref<MotoCatalogo[]>([])
const maxUnidades = ref(10)
const token = ref('')

const clienteEncontrado = ref(false)
const clienteEstado = ref('Si el documento ya está registrado se usa ese cliente; no se crea otro.')
const clienteEstadoTipo = ref<'neutro' | 'encontrado' | 'nuevo'>('neutro')

function resetCliente() {
  clienteEncontrado.value = false
  clienteEstadoTipo.value = 'neutro'
  clienteEstado.value = 'Si el documento ya está registrado se usa ese cliente; no se crea otro.'
}

async function buscarCliente(documento: string) {
  const docLimpio = documento.replace(/[\s.]/g, '')
  if (!docLimpio) return
  buscandoCliente.value = true
  try {
    const res = await ventasApi.buscarCliente(docLimpio)
    if (res.encontrado && res.cliente) {
      clienteEncontrado.value = true
      formRef.value?.llenarCliente(res.cliente)
      clienteEstadoTipo.value = 'encontrado'
      clienteEstado.value = `Cliente encontrado: ${res.cliente.nombre}. Se usará este registro.`
    } else {
      clienteEncontrado.value = false
      clienteEstadoTipo.value = 'nuevo'
      clienteEstado.value = 'Cliente nuevo: completa los datos para registrarlo con la venta.'
    }
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
    }
  } finally {
    buscandoCliente.value = false
  }
}

async function registrarVenta(payload: RegistrarVentaPayload) {
  error.value = null
  enviando.value = true
  try {
    const res = await ventasApi.registrar(payload)
    await router.push({
      path: `/ventas/${res.id}`,
      query: { mensaje: res.mensaje },
    })
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo registrar la venta.'
  } finally {
    enviando.value = false
  }
}

onMounted(async () => {
  try {
    const data = await ventasApi.catalogo()
    catalogo.value = data.catalogo
    maxUnidades.value = data.maxUnidades
    token.value = data.token
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      await router.push('/login')
      return
    }
    error.value = err instanceof Error ? err.message : 'No se pudo cargar el catálogo.'
  } finally {
    cargandoCatalogo.value = false
  }
})
</script>

<template>
  <header class="vp-cabecera">
    <div>
      <p class="vp-antetitulo">Punto de venta</p>
      <h1>Nueva venta</h1>
      <p class="vp-subtitulo">La venta queda sin factura hasta que se confirme el pago.</p>
    </div>
    <RouterLink class="btn btn-secundario" to="/ventas">Volver al listado</RouterLink>
  </header>

  <div v-if="error" class="alerta alerta-error">{{ error }}</div>

  <div v-if="cargandoCatalogo" class="tarjeta">
    <div class="vp-skeleton"></div>
  </div>

  <VentaCrearForm
    v-else
    ref="formRef"
    :catalogo="catalogo"
    :max-unidades="maxUnidades"
    :token="token"
    :enviando="enviando"
    :buscando-cliente="buscandoCliente"
    :cliente-estado="clienteEstado"
    :cliente-estado-tipo="clienteEstadoTipo"
    :cliente-encontrado="clienteEncontrado"
    @buscar-cliente="buscarCliente"
    @reset-cliente="resetCliente"
    @enviar="registrarVenta"
  />
</template>
