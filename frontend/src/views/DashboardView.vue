<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ventasApi } from '@/api/ventasApi'
import { formatoPesos } from '@/utils/formato'

const cargando = ref(true)
const error = ref<string | null>(null)
const totalVentas = ref(0)
const pendientes = ref(0)
const pagadas = ref(0)
const montoTotal = ref(0)

const modulosBloqueados = [
  {
    nombre: 'Créditos y financiación',
    descripcion: 'Radicación de solicitudes, checklist Brilla / ServiCrédito y dictamen.',
  },
  {
    nombre: 'Taller y mantenimientos',
    descripcion: 'Registro de servicios de taller y control de mantenimientos de garantía.',
  },
  {
    nombre: 'Garantía',
    descripcion: 'Consulta de vigencia por placa, chasis o número de factura.',
  },
  {
    nombre: 'Inventario',
    descripcion: 'Control de stock de motocicletas y productos disponibles.',
  },
  {
    nombre: 'Promociones',
    descripcion: 'Descuentos vigentes por marca, gama o modelo.',
  },
]

const totalFormateado = computed(() => formatoPesos(montoTotal.value))

onMounted(async () => {
  try {
    const data = await ventasApi.listar()
    totalVentas.value = data.ventas.length
    pendientes.value = Number(data.conteo.PENDIENTE ?? 0) + Number(data.conteo.EN_CREDITO ?? 0)
    pagadas.value =
      Number(data.conteo.PAGADA ?? 0) +
      Number(data.conteo.ENTREGADA ?? 0) +
      Number(data.conteo.CERRADA ?? 0)
    montoTotal.value = data.ventas.reduce((acc, v) => acc + Number(v.total || 0), 0)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'No se pudieron cargar las métricas.'
  } finally {
    cargando.value = false
  }
})
</script>

<template>
  <header class="vp-cabecera">
    <div>
      <p class="vp-antetitulo">Panel administrativo</p>
      <h1>Dashboard MOTOCONS</h1>
      <p class="vp-subtitulo">
        Vista general del sistema. Solo el módulo de Ventas en tienda está habilitado en este panel.
      </p>
    </div>
    <RouterLink class="btn" to="/ventas/nueva">Nueva venta</RouterLink>
  </header>

  <div v-if="error" class="alerta alerta-error">{{ error }}</div>

  <section class="dash-metricas" aria-label="Resumen de ventas">
    <article class="tarjeta dash-metrica es-primaria">
      <p class="vp-antetitulo">Total ventas</p>
      <p class="dash-metrica-valor">{{ cargando ? '—' : totalVentas }}</p>
    </article>
    <article class="tarjeta dash-metrica">
      <p class="vp-antetitulo">Pendientes / Crédito</p>
      <p class="dash-metrica-valor">{{ cargando ? '—' : pendientes }}</p>
    </article>
    <article class="tarjeta dash-metrica">
      <p class="vp-antetitulo">Pagadas / Entregadas</p>
      <p class="dash-metrica-valor">{{ cargando ? '—' : pagadas }}</p>
    </article>
    <article class="tarjeta dash-metrica">
      <p class="vp-antetitulo">Volumen registrado</p>
      <p class="dash-metrica-valor">{{ cargando ? '—' : totalFormateado }}</p>
    </article>
  </section>

  <h2>Módulos del concesionario</h2>
  <section class="dash-modulos" aria-label="Módulos del sistema">
    <RouterLink class="tarjeta dash-modulo-activo" to="/ventas">
      <div>
        <p class="vp-antetitulo">Disponible</p>
        <h3>Ventas en tienda</h3>
        <p class="vp-nota">
          Registra la venta de motos en el almacén, confirma el pago, actualiza estados y genera la
          factura.
        </p>
      </div>
      <span class="btn btn-chico">Abrir Ventas en tienda</span>
    </RouterLink>

    <article
      v-for="mod in modulosBloqueados"
      :key="mod.nombre"
      class="tarjeta dash-modulo-bloqueado"
      aria-disabled="true"
    >
      <div>
        <p class="vp-antetitulo">No disponible</p>
        <h3>{{ mod.nombre }}</h3>
        <p class="vp-nota">{{ mod.descripcion }}</p>
      </div>
      <span class="admin-nav-badge" style="align-self: flex-start">Solo lectura / Bloqueado</span>
    </article>
  </section>
</template>
