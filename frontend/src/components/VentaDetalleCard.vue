<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import type { EstadoVenta, VentaDetalle } from '@/types/ventas'
import { claseEstado, formatoPesos } from '@/utils/formato'

const props = defineProps<{
  venta: VentaDetalle
  estados: Record<EstadoVenta, string>
  acciones: Partial<Record<EstadoVenta, string>>
  procesando: boolean
}>()

const emit = defineEmits<{
  (e: 'cambiar-estado', destino: EstadoVenta): void
}>()

const notasEstado: Partial<Record<EstadoVenta, string>> = {
  PENDIENTE: 'Al confirmar el pago se asigna la factura y las motos pasan a garantía y taller.',
  EN_CREDITO:
    'Esperando el dictamen de financiación. Si aprueban con cuota inicial, cóbrala y confirma el pago.',
  PAGADA: 'Registra la entrega cuando el cliente se lleve la moto.',
}

const pasoActual = computed(() => {
  const mapa: Record<EstadoVenta, number> = {
    PENDIENTE: 0,
    EN_CREDITO: 0,
    PAGADA: 1,
    ENTREGADA: 2,
    CERRADA: 3,
    ANULADA: -1,
  }
  return mapa[props.venta.estado] ?? -1
})

const recorrido = computed(() => [
  props.venta.estado === 'EN_CREDITO' ? 'En crédito' : 'Registrada',
  'Pagada',
  'Entregada',
  'Cerrada',
])

const listaAcciones = computed(
  () => Object.entries(props.acciones) as Array<[EstadoVenta, string]>,
)

function confirmarYCambiar(destino: EstadoVenta) {
  if (destino === 'ANULADA') {
    const ok = window.confirm('¿Seguro que quieres anular esta venta? No se puede deshacer.')
    if (!ok) return
  }
  emit('cambiar-estado', destino)
}
</script>

<template>
  <div v-if="venta.estado === 'ANULADA'" class="alerta alerta-aviso">
    Esta venta fue anulada. Sus motos ya no aparecen en garantía ni en el taller.
  </div>
  <ol v-else class="vp-recorrido" aria-label="Avance de la venta">
    <li
      v-for="(texto, i) in recorrido"
      :key="texto"
      :class="{
        'es-hecho': i < pasoActual,
        'es-actual': i === pasoActual,
      }"
    >
      {{ texto }}
    </li>
  </ol>

  <div class="vp-detalle">
    <section class="tarjeta">
      <h2>Cliente</h2>
      <dl class="vp-datos">
        <dt>Nombre</dt>
        <dd>{{ venta.cliente }}</dd>
        <dt>Documento</dt>
        <dd>{{ venta.documento }}</dd>
        <dt>Correo</dt>
        <dd>
          <template v-if="venta.correo">{{ venta.correo }}</template>
          <span v-else class="vp-suave">Sin correo</span>
        </dd>
        <dt>Teléfono</dt>
        <dd>
          <template v-if="venta.telefono">{{ venta.telefono }}</template>
          <span v-else class="vp-suave">Sin teléfono</span>
        </dd>
      </dl>

      <h2>Motos</h2>
      <div class="vp-tabla">
        <table class="tabla">
          <thead>
            <tr>
              <th>Modelo</th>
              <th>Chasis</th>
              <th>Placa</th>
              <th>Garantía</th>
              <th class="vp-numero-col">Precio</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(d, idx) in venta.detalle" :key="idx">
              <td>
                <strong>{{ d.marca }} {{ d.modelo }}</strong>
                <small>{{ d.propulsion === 'electrica' ? 'Eléctrica' : 'Combustión' }}</small>
              </td>
              <td class="vp-codigo">{{ d.chasis }}</td>
              <td>
                <template v-if="d.placa">{{ d.placa }}</template>
                <span v-else class="vp-suave">Sin placa</span>
              </td>
              <td>
                {{ d.meses_garantia }} meses
                <a
                  v-if="d.vehiculo_id !== null"
                  class="vp-enlace"
                  :href="`/index.php?r=garantia/index&criterio=chasis&valor=${encodeURIComponent(d.chasis)}`"
                >
                  Ver garantía
                </a>
              </td>
              <td class="vp-numero-col">{{ formatoPesos(d.precio) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="vp-fila-total">
              <td colspan="4">Total</td>
              <td class="vp-numero-col">{{ formatoPesos(venta.total) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>

    <aside class="tarjeta vp-estado-tarjeta" :class="claseEstado(venta.estado)">
      <p class="vp-antetitulo">Estado</p>
      <span class="vp-estado vp-estado-grande" :class="claseEstado(venta.estado)">
        {{ estados[venta.estado] ?? venta.estado }}
      </span>
      <p v-if="notasEstado[venta.estado]" class="vp-nota">
        {{ notasEstado[venta.estado] }}
      </p>

      <div v-if="listaAcciones.length > 0" class="vp-acciones">
        <button
          v-for="([destino, texto], idx) in listaAcciones"
          :key="destino"
          type="button"
          class="btn btn-bloque"
          :class="{
            'btn-peligro': destino === 'ANULADA',
            'btn-secundario': destino !== 'ANULADA' && idx > 0,
          }"
          :disabled="procesando"
          @click="confirmarYCambiar(destino)"
        >
          {{ texto }}
        </button>
        <RouterLink class="btn btn-secundario btn-bloque" :to="`/ventas/${venta.id}/editar`">
          Abrir formulario de edición
        </RouterLink>
      </div>
      <p v-else class="vp-nota">Esta venta ya no tiene más cambios de estado.</p>
    </aside>
  </div>
</template>
